#!/usr/bin/php
<?php
/**
 * Process CCRS Incoming Files
 *
 * SPDX-License-Identifier: MIT
 */

use \Edoceo\Radix\DB\SQL;

require_once(__DIR__ . '/../boot.php');

openlog('openthc-bong', LOG_ODELAY | LOG_PID, LOG_LOCAL0);

// Lock
$lock = new \OpenTHC\CLI\Lock(implode('/', [ __FILE__, $cli_args['--license'] ]));
if ( ! $lock->create()) {
	syslog(LOG_DEBUG, 'Lock: Failed to Create');
	exit(0);
}

$dbc = _dbc();

// The Compliance Engine
$cfg = \OpenTHC\CRE::getConfig('usa/wa');
$cre = \OpenTHC\CRE::factory($cfg);
$tz0 = new DateTimezone($cfg['tz']);
$dt0 = new \DateTime('now', $tz0);

// Process incoming message queue
$message_path = sprintf('%s/var/ccrs-incoming-mail/*.txt', APP_ROOT);
$x = getenv('IMPORT_FAIL');
if ( ! empty($x)) {
	$message_path = sprintf('%s/var/ccrs-incoming-fail/*.txt', APP_ROOT);
}
$message_file_list = glob($message_path);
syslog(LOG_DEBUG, sprintf('Import Message File Count: %d', count($message_file_list)) );
foreach ($message_file_list as $message_file)
{
	syslog(LOG_DEBUG, sprintf('message-file: %s', $message_file));

	$RES = new \OpenTHC\Bong\CRE\CCRS\Response($message_file);
	$RES->isValid();

	// Should Write Result Blob Here
	$arg = [ ':r0' => $RES->req_ulid ];
	$sql = <<<SQL
	SELECT id
		, license_id
		, created_at
		, name
		, stat
		, res_info
		, result_data
	FROM log_upload
	WHERE id = :r0
	SQL;
	$REQ = $dbc->fetchRow($sql, $arg);
	if (empty($REQ['id'])) {
		throw new \Exception('Invalid Response [CCP-119]');
	}
	$RES->license_id = $REQ['license_id'];
	$RES->req_stat = $REQ['stat'];

	// Stash the Response
	$res_info = json_decode($REQ['res_info'], true);
	$result_data = json_decode($REQ['result_data'], true);
	if (empty($result_data)) {
		$result_data = [];
	}

	if ( ! empty($message_file)) {
		$result_data['@result-mail'] = file_get_contents($message_file);
	}

	// If CSV Save Here
	if (preg_match('/\.csv$/', $RES->res_file)) {

		$csv_time = $cre->csv_file_date($RES->res_file);

		$res_info['file'] = basename($RES->res_file);
		$res_info['size'] = strlen(file_get_contents($RES->res_file));

		$result_data['@result-file'] = [
			'name' => basename($RES->res_file),
			'data' => file_get_contents($RES->res_file),
			'meta' => [
				'created_at' => $dt0->format(\DateTime::RFC3339),
				'created_at_cre' => $csv_time->format(\DateTimeInterface::RFC3339),
			]
		];
	}

	$update = [
		'res_info' => json_encode($res_info),
		'result_data' => json_encode($result_data),
		'updated_at' => $dt0->format(\DateTime::RFC3339)
	];

	$dbc->update('log_upload', $update, [ 'id' => $RES->req_ulid ]);

	// Should these all just pass back a RESULT object?

	$upload_result = [];

	// echo "\$RES->type == {$RES->type}\n";
	switch ($RES->type) {
		case 'ccrs-failure-full':
			// NOthing To do?  What?
			$upload_result = _ccrs_pull_failure_full($RES, $message_file);
			break;
		case 'ccrs-failure-data':
			$upload_result = _ccrs_pull_failure_data($RES, $message_file);
			break;
		case 'b2b-outgoing-manifest':
			$upload_result = _ccrs_pull_manifest_file($message_file, $RES);
			break;
		case 'ccrs-success':
			$upload_result = _ccrs_pull_success($RES, $message_file, $message_head, $message_body);
			break;
		default:
			throw new \Exception('Invalid Message Type');
	}
	// var_dump($upload_result);

	$res_pull = '100';
	if ( ! empty($upload_result['code'])) {
		$res_pull = $upload_result['code'];
	}

	// Set Redis Success
	$tab_name = _ccrs_object_name_map($RES->req_type);

	// Update Record in Database?
	$status = new \OpenTHC\Bong\CRE\CCRS\Status($RES->license_id, $tab_name);
	$status->setPull($res_pull);
	// $status->setData($res_pull);

	// Move Message File

	// Move Message-Attachment File

}

// Cleanup Legacy Data Files
$file_list = glob(sprintf('%s/var/ccrs-incoming/*.csv', APP_ROOT));
foreach ($file_list as $file) {
	// Patch Text Errors?
	if (preg_match('/(\w+)_\w+_(\w+)_(\w+)\.csv/', $file, $m)) {
		$RES = new \OpenTHC\Bong\CRE\CCRS\Response('');
		$RES->req_type = $m[1];
		$RES->req_ulid = $m[2];
		$RES->res_file = $file;
		$RES->ccrs_datetime = $m[3];
		_csv_file_incoming($RES, $file);
	} elseif (preg_match('/(\w+)_\w+_(\w+)\.csv/', $file, $m)) {

	// } elseif (preg_match('/Area_\w+_(\w+)\.csv/', $file, $m)) {
	//	// This was a odd one that showed up, was missing the upload ID in the filename
	//	// Which is usually present
	//	// Exception: Cannot match file "/opt/openthc/bong/var/ccrs-incoming/Area_AF0E72B77C_20250215T174846049.csv"'
	//  // Exception: Cannot match file "/opt/openthc/bong/var/ccrs-incoming/InventoryTransfer_AF0E72B77C_20250317T100653185.csv"
	//	// And that file was a test upload
		$RES = new \OpenTHC\Bong\CRE\CCRS\Response('');
		$RES->req_type = $m[1];
		$RES->req_ulid = '';
		$RES->res_file = $file;
		$RES->ccrs_datetime = $m[2];
		_csv_file_incoming($RES, $file);
	} elseif (preg_match('/Manifest\w+_\w+_(\w+)\.csv/', $file)) {
		$RES = new \OpenTHC\Bong\CRE\CCRS\Response('');
		$RES->res_file = $file;
		$RES->ccrs_datetime = $m[1];
		_csv_file_incoming($RES, $file);
	} else {
		throw new \Exception(sprintf('Cannot match file "%s"', $file));
	}
}

$dt0 = new \DateTime();
$rdb = \OpenTHC\Service\Redis::factory();
$rdb->hset('/cre/ccrs', 'pull/time', $dt0->format(\DateTimeInterface::RFC3339));

function _ccrs_object_name_map($t) : string {

	switch (strtoupper($t)) {
	case 'AREA':
		return 'section';
	case 'MANIFEST':
		return 'b2b/outgoing/file';
	case 'PLANT':
		return 'crop';
	case 'HARVEST':
		return 'crop/collect';
	case 'SALE':
		return 'b2b/outgoing';
	case 'STRAIN':
		return 'variety';
	case 'INVENTORYTRANSFER':
		return 'b2b/incoming';
		break;
	case 'INVENTORY':
	case 'PRODUCT';
		return strtolower($t);
	default:
		throw new \Exception(sprintf('Invalid Object Type "%s"', $t));
	}

}

/**
 *
 */
// }, $message_file) {
function _ccrs_pull_success($RES, $message_file) { //

	global $dbc, $tz0, $dt0;

	$message_full = file_get_contents($message_file);

	$tab_name = _ccrs_object_name_map($RES->req_type);

	// Update the Database
	$sql = <<<SQL
	UPDATE log_upload SET stat = 202, result_data = coalesce(result_data, '{}'::jsonb) || :d0
	WHERE id = :r0
	SQL;

	$res = $dbc->query($sql, [
		':r0' => $RES->req_ulid,
		':d0' => json_encode([
			'type' => '',
			'data' => '',
			'@result' => [
				'type' => 'mail',
				'data' => $message_full,
				'created_at' => $RES->mail_datetime->format(\DateTime::RFC3339),
				'created_at_cre' => $RES->mail_datetime->format(\DateTime::RFC3339)
				// Parsing is Buggy
				// $RES->ccrs_datetime->format(
			]
		])
	]);
	if (1 != $res) {
		throw new \Exception('Cannot find in Upload Log');
	}

	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

	// Get some Time Stats


	return [
		'code' => 200,
	];

}


/**
 *
 */
function _ccrs_pull_failure_data($RES, string $message_file) : array  {

	_csv_file_incoming($RES, $RES->res_file);

	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

/*
	_notify_app([
		'message' => 'b2b-outgoing-failure',
		'company' => [
			'id' => $License['company_id'],
		],
		'license' => [
			'id' => $License['id'],
		],
		'b2b' => [
			'id' => $b2b_outgoing['id'],
			'stat' => $b2b_outgoing['stat'],
		],
	]);
*/

	return [
		'code' => 400,
	];
}

/**
 * Process Full Failure
 * The CSV "has not been processed"
 */
function _ccrs_pull_failure_full($RES, string $message_file) : int
{
	global $dbc, $tz0;

	$message_full = file_get_contents($message_file);
	$message_text = strip_tags($RES->res_body);

	$dt0 = new \DateTime('now', $tz0);

	$sql = <<<SQL
	UPDATE log_upload SET stat = 400, result_data = coalesce(result_data, '{}'::jsonb) || :d0
	WHERE id = :r0
	SQL;

	$res = $dbc->query($sql, [
		':r0' => $RES->req_ulid,
		':d0' => json_encode([
			'type' => '',
			'data' => '',
			'@result' => [
				'type' => 'mail',
				'data' => $message_full,
				'created_at' => $dt0->format(\DateTime::RFC3339),
				'created_at_cre' => $RES->mail_datetime->format(\DateTime::RFC3339)
			]
		])
	]);
	if (1 != $res) {
		throw new \Exception('Cannot find in Upload Log');
	}

	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

	return 0;

}

/**
 * Pull the Manifest PDF File into BONG
 */
function _ccrs_pull_manifest_file(string $message_file, $RES) : array {

	global $dbc;

	$output_file = $RES->res_file;
	echo "_ccrs_pull_manifest_file($message_file, $output_file)\n";

	if (empty($output_file)) {
		echo "Failed to Process: $message_file [CCP-161]\n";
		exit(1);
	}
	if ( ! is_file($output_file)) {
		echo "Failed to Process: $message_file [CCP-165]\n";
		exit(1);
	}
	if (0 == filesize($output_file)) {
		echo "Failed to Process: $message_file [CCP-169]\n";
		exit(1);
	}
	if ( ! preg_match('/Manifest_(.+)_\w+\.pdf/', $output_file, $m)) {
		echo "Failed to Process: $message_file [CCP-177]\n";
		exit(1);
	}
	$manifest_id = $m[1];

	$b2b_outgoing = $dbc->fetchRow('SELECT id, source_license_id FROM b2b_outgoing WHERE id = :m0', [
		':m0' => $manifest_id
	]);
	if (empty($b2b_outgoing['id'])) {
		syslog(LOG_NOTICE, "Manifest '$manifest_id' from attachment filename not found");
		// var_dump($RES);
		$src = $dbc->fetchOne('SELECT source_data FROM log_upload WHERE id = :r0', [
			':r0' => $RES->req_ulid
		]);
		// $src = json_decode($src);
		// $src_data = $src->data;
		// var_dump($src_data);
		if (preg_match('/ExternalManifestIdentifier,(\w+),,,/', $src, $m)) {
			// var_dump($m);
			$manifest_id = $m[1];
			$b2b_outgoing = $dbc->fetchRow('SELECT id, source_license_id FROM b2b_outgoing WHERE id = :m0', [
				':m0' => $manifest_id
			]);
		}
	}
	if (empty($b2b_outgoing['id'])) {
		$message_file_fail = str_replace('/ccrs-incoming-mail/', '/ccrs-incoming-fail/', $message_file);
		// rename($message_file, $message_file_fail);
		// var_dump($RES);
		// exit;
		throw new \Exception("Failed to Process: '$message_file'; Missing B2B Outgoing '$manifest_id' [CCP-186]");
	}


	$License = $dbc->fetchRow('SELECT id, company_id FROM license WHERE id = :l0', [
		':l0' => $b2b_outgoing['source_license_id']
	]);


	// Update b2b_outgoing with some stat or flag?
	$sql = <<<SQL
	INSERT INTO b2b_outgoing_file (id, name, body) VALUES (:b2b0, :n1, :b1)
	ON CONFLICT (id) DO
	UPDATE SET name = EXCLUDED.name, body = EXCLUDED.body
	SQL;

	$cmd = $dbc->prepare($sql, null);
	$cmd->bindParam(':b2b0', $b2b_outgoing['id']);
	$cmd->bindParam(':n1', basename($output_file));
	$cmd->bindParam(':b1', file_get_contents($output_file), \PDO::PARAM_LOB);
	$cmd->execute();

	// How to Find the CCRS Upload to Re-Map?
	$message_data = file_get_contents($message_file);
	if (preg_match('/Manifest_\w+_(\w+)_\d+T\d+\.csv/', $message_data, $m)) {

		// Need to Trap the Email Here Too
		$sql = <<<SQL
		UPDATE log_upload SET stat = 202, result_data = coalesce(result_data, '{}'::jsonb) || :d0
		WHERE id = :r0
		SQL;
		$res = $dbc->query($sql, [
			':r0' => $RES->req_ulid,
			':d0' => json_encode([
				'type' => '',
				'data' => '',
				'@result' => [
					'type' => 'mail',
					'data' => $message_data,
					// 'created_at' => $dt0->format(\DateTime::RFC3339),
					// 'created_at_cre' => $dt1->format(\DateTime::RFC3339)
				]
			])
		]);

	}

	// Notify the Primary Application
	$res = _notify_app([
		'message' => 'b2b-outgoing-notify',
		'company' => [
			'id' => $License['company_id'],
		],
		'license' => [
			'id' => $License['id'],
		],
		'b2b' => [
			'id' => $b2b_outgoing['id'],
			'file' => [
				'name' => basename($output_file),
				'body' => file_get_contents($output_file),
			],
		],
	]);

	// Archive File
	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

	unlink($output_file);

	return [
		'code' => 200,
	];

}

/**
 *
 */
function _csv_file_incoming($RES, string $csv_file) : bool
{
	global $cre, $dbc, $tz0, $dt0;

	echo "_csv_file_incoming($csv_file)\n";
	// var_dump($RES);

	$cre_stat = 200;
	$lic_code = null;
	$lic_data = [];
	$lic_dead = false;

	// Need to keep file name to understand the data-type
	$csv_pipe = fopen($csv_file, 'r');
	$idx_line = 1;
	$csv_head = fgetcsv($csv_pipe);
	$csv_head = array_values($csv_head);
	$row_size = count($csv_head);

	$csv_pkid = 'ExternalIdentifier';

	// Assemble Header Line to determine Type
	$tab_name = '';
	$tmp_name = implode(',', $csv_head);
	switch ($tmp_name) {
		case 'FromLicenseNumber,ToLicenseNumber,FromInventoryExternalIdentifier,ToInventoryExternalIdentifier,Quantity,TransferDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_incoming_item';
			break;
		case 'LicenseNumber,Name,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage': // v2021-340
		case 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage': // v2022-343
			// Section is also used for the PING test
			$tab_name = 'section';
			break;
		case 'CreatedBy,CreatedDate,ErrorMessage,LicenseNumber,ExternalIdentifier,UpdatedBy,UpdatedDate,Operation,Area,Strain,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,InventoryIdentifier':
			$tab_name = 'inventory';
			break;
		case 'LicenseNumber,InventoryExternalIdentifier,AdjustmentReason,AdjustmentDetail,Quantity,AdjustmentDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'inventory_adjust';
			break;
		case 'LicenseNumber,PlantIdentifier,Area,Strain,PlantSource,PlantState,GrowthStage,HarvestCycle,MotherPlantExternalIdentifier,HarvestDate,IsMotherPlant,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
		case 'LicenseNumber,PlantIdentifier,Area,Strain,PlantSource,PlantState,GrowthStage,MotherPlantExternalIdentifier,HarvestDate,IsMotherPlant,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'crop';
			break;
		case 'LicenseNumber,PlantIdentifier,InventoryExternalIdentifier,ExternalIdentifier,InventoryType,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'crop_collect';
			break;
		case 'LicenseNumber,PlantExternalIdentifier,DestructionReason,DestructionDetail,DestructionMethod,DestructionDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$csv_pkid = 'PlantExternalIdentifier';
			$tab_name = 'crop_finish';
			break;
		case 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage,UnitWeightGrams':
			$tab_name = 'product';
			break;
		case 'Strain,StrainType,CreatedBy,CreatedDate,ErrorMessage': // v0
			// @todo this one needs special processing ?
			$csv_pkid = 'Strain';
			$tab_name = 'variety';
			break;
		case 'LicenseNumber,Strain,CreatedBy,CreatedDate,StrainType,ErrorMessage': // v1
			$csv_pkid = 'Strain';
			$tab_name = 'variety';
			break;
		case 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,SalesTax,OtherTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
		case 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,RetailsSalesTax,CannabisExciseTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_outgoing_item';
			break;
		case 'Submittedby,SubmittedDate,NumberRecords,ExternalManifestIdentifier,HeaderOperation,TransportationType,OriginLicenseNumber,OriginLicenseePhone,OriginLicenseeEmailAddress,TransportationLicenseNumber,DriverName,DepartureDateTime,ArrivalDateTime,VIN#,VehiclePlateNumber,VehicleModel,VehicleMake,VehicleColor,DestinationLicenseNumber,DestinationLicenseePhone,DestinationLicenseeEmailAddress,ErrorMessage':
			$tab_name = 'b2b_outgoing_manifest';
			_process_csv_file_b2b_outgoing_manifest($RES, $csv_file, $csv_pipe, $csv_head);
			return false;
			break;
		case 'InventoryExternalIdentifier,PlantExternalIdentifier,Quantity,UOM,WeightPerUnit,ServingsPerUnit,ExternalIdentifier,LabTestExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_outgoing_manifest_line_item';
			_process_csv_file_b2b_outgoing_manifest_item($RES, $csv_file, $csv_pipe, $csv_head);
			return false;
			break;
		default:
			throw new Exception("CSV Header Not Handled\n$tab_name");
			break;
	}

	// Spin the CSV File
	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;

		// Skip Canary Line
		$csv_line_text = implode(',', $csv_line);
		if (preg_match('/UPLOAD.+01\w{24}.+\-canary\-/', $csv_line_text)) {
			continue;
		}

		if (count($csv_head) != count($csv_line)) {
			var_dump($csv_head);
			var_dump($csv_line);
			echo "Two Lines Don't Match\n";
			exit(1);
		}

		$csv_line = array_combine($csv_head, $csv_line);
		$csv_line['@id'] = $csv_line[$csv_pkid];

		// Handle some CCRS Switch-Over Shit
		switch ($tab_name) {
			case 'b2b_incoming_item':
				$csv_line['LicenseNumber'] = $csv_line['ToLicenseNumber'];
				break;
			case 'b2b_outgoing_item':
				$csv_line['@id'] = $csv_line['SaleDetailExternalIdentifier'];
				// $csv_line['LicenseNumber'] = $csv_line['FromLicenseNumber'];
				break;
			case 'variety':
				$csv_line['@id'] = strtoupper($csv_line['@id']);
				break;
		}

		// Work Around for https://github.com/openthc/ccrs/issues/41
		if (empty($csv_line['LicenseNumber'])) {
			if ( ! empty($log_data['license_id'])) {
				$csv_line['LicenseNumber'] = $dbc->fetchOne('SELECT code FROM license WHERE id = :l0', [
					':l0' => $log_data['license_id']
				]);
			}
		}

		// Discover License
		if (empty($lic_code)) {

			$lic_code = $csv_line['LicenseNumber'];
			$lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

		} elseif ($lic_code != $csv_line['LicenseNumber']) {

			echo "'{$lic_code}' != '{$csv_line['LicenseNumber']}'\n";

			throw new \Exception('SWITCHING LICENSE [BCC-218]');

		}

		$err = $RES->errorExtractFromLine($csv_line);
		// var_dump($err);

		$err_list = $err['data'];

		switch ($err['code']) {
			case 200: // Awesome
			case 202: // Only for Variety data
				// OK
				break;
			case 400: // Somekind of Errors
			case 404: // Not Found (should INSERT)
				$cre_stat = $err['code'];
				break;
			case 403: // Not Authorized on this License
				$cre_stat = $err['code'];
				$lic_dead = true;
				break;
			default:
				var_dump($csv_line);
				var_dump($err);
				var_dump($err_list);
				throw new \Exception('WTF');
		}

		// Result
		if (count($err_list)) {

			$err_line = implode('; ', $err_list);
			$out_line = implode(',', $csv_line);

			// echo sprintf("%04d:%s\n    !!%s\n", $idx_line, $out_line, $err_line);

		}

		// Inflate the Old Data
		$rec_data = [];
		switch ($tab_name) {
			case 'b2b_incoming_item':
				$rec_data = $dbc->fetchOne("SELECT data FROM {$tab_name} WHERE id = :pk", [
					':pk' => $csv_line['@id']
				]);
				if (empty($rec_data)) {
					// Fake It
					$rec_data = [
						'@version' => 'ccrs-res/2022',
						'@source' => $csv_line,
					];
				} else {
					$rec_data = json_decode($rec_data, true);
				}

				break;

			case 'b2b_outgoing_item':

				$rec_data = $dbc->fetchOne("SELECT data FROM {$tab_name} WHERE id = :pk", [
					':pk' => $csv_line['@id']
				]);
				if (empty($rec_data)) {
					// Fake It
					$rec_data = [
						'@version' => 'ccrs-res/2022',
						'@source' => $csv_line,
					];
				} else {
					$rec_data = json_decode($rec_data, true);
				}

				break;

			default:

				$rec_data = $dbc->fetchOne("SELECT data FROM {$tab_name} WHERE license_id = :l0 AND id = :pk", [
					':l0' => $RES->license_id,
					':pk' => $csv_line['@id']
				]);

				if (empty($rec_data)) {
					// Fake It
					$rec_data = [
						'@version' => 'ccrs-res/2022',
						'@source' => $csv_line,
					];
				} else {
					$rec_data = json_decode($rec_data, true);
				}

				break;

		}

		$rec_data['@result'] = $err;

		// Special Case Two Table Inserts
		switch ($tab_name) {
			case 'b2b_incoming_item':
			case 'b2b_outgoing_item':

				$sql = <<<SQL
				UPDATE {$tab_name} SET
					stat = :s1,
					data = :d1,
					updated_at = now()
				WHERE id = :pk
				SQL;
				$arg = [
					':pk' => $csv_line['@id'],
					':s1' => $err['code'],
					':d1' => json_encode($rec_data)
				];
				$chk = $dbc->query($sql, $arg);
				switch ($chk) {
				case 0:
					// No Record to Update?
					break;
				case 1:
					// OK
					break;
				default:
					var_dump($csv_line);
					var_dump($rec_data);
					echo "FAIL: $chk != 1; ";
					echo $dbc->_sql_debug($sql, $arg);
					echo "\n";
					exit;
				}

				break;

			default:

				if (empty($rec_data['@result'])) {
					unset($rec_data['@result']);
				}

				$sql = <<<SQL
				UPDATE {$tab_name} SET
					stat = :s1,
					data = :d1,
					updated_at = now()
				WHERE license_id = :l0 AND id = :pk
				SQL;
				$arg = [
					':l0' => $RES->license_id,
					':pk' => $csv_line['@id'],
					':s1' => $err['code'],
					':d1' => json_encode($rec_data)
				];
				$chk = $dbc->query($sql, $arg);
				if (1 != $chk) {
					$out = true;
					// Ignore Errors For Section 'PING';
					if ('section' == $tab_name) {
						if (('Main Section' == $csv_line['Area']) || ('OPENTHC SECTION PING' == $csv_line['Area'])) {
							if ('DELETE' == $csv_line['Operation']) {
								$out = false;
							}
						}
					}
					if ($out) {
						echo "FAIL: $chk != 1; ";
						echo $dbc->_sql_debug($sql, $arg);
						echo "\n";
					}
				}

		}

	}

	switch ($tab_name) {
		case 'b2b_incoming_item':

			// var_dump($err_list);
			// var_dump($rec_data);
			// exit;

			syslog(LOG_WARNING, "upload:{$RES->req_ulid} b2b-incoming:$lic_code; stat:??? cre_stat=$cre_stat");

			$sql = <<<SQL
			UPDATE b2b_incoming
			SET stat = (
				SELECT coalesce(max(b2b_incoming_item.stat), 100)
				FROM b2b_incoming_item
				WHERE b2b_incoming_item.b2b_incoming_id = b2b_incoming.id
			)
			WHERE b2b_incoming.target_license_id = :l0 AND b2b_incoming.stat != 202
			SQL;

			$ret = $dbc->query($sql, [ ':l0' => $RES->license_id ]);

			echo "UPDATED B2B INCOMING: $ret\n";

			break;

		case 'b2b_outgoing_item':

			syslog(LOG_WARNING, "upload:{$RES->req_ulid} b2b-outgoing:$lic_code; stat:??? cre_stat=$cre_stat");

			$sql = <<<SQL
			UPDATE b2b_outgoing
			SET stat = (
				SELECT coalesce(max(b2b_outgoing_item.stat), 100)
				FROM b2b_outgoing_item
				WHERE b2b_outgoing_item.b2b_outgoing_id = b2b_outgoing.id
			)
			WHERE b2b_outgoing.source_license_id = :l0 AND b2b_outgoing.stat != 202
			SQL;

			$ret = $dbc->query($sql, [ ':l0' => $RES->license_id ]);

			echo "UPDATED B2B OUTGOING: $ret\n";

			break;
	}

	// Only if a License as Set
	if ($lic_dead) {

		syslog(LOG_WARNING, "upload:{$RES->req_ulid} license:$lic_code; stat:403");
		// $license_stat_list1[ $lic_code ]['flag1'] = ($license_stat_list1[ $lic_code ]['flag'] & ~LICENSE_FLAG_CRE_HAVE);
		// $license_stat_list1[ $lic_code ]['stat1'] = 403;
		$sql = 'UPDATE license SET stat = 403 WHERE id = :l0 AND stat != 403';
		$arg = [
			':l0' => $RES->license_id
		];
		$dbc->query($sql, $arg);

		// Update the Other Object Records
		// $sql = 'UPDATE variety SET stat = 403 WHERE license_id = :l0 AND stat != 403';
		// $dbc->query($sql, $arg);

		// $sql = 'UPDATE section SET stat = 403 WHERE license_id = :l0 AND stat != 403';
		// $dbc->query($sql, $arg);

		// $sql = 'UPDATE product SET stat = 403 WHERE license_id = :l0 AND stat != 403';
		// $dbc->query($sql, $arg);

	} else {

		$sql = 'UPDATE license SET stat = 200 WHERE id = :l0 AND stat != 200';
		$arg = [
			':l0' => $RES->license_id
		];
		$dbc->query($sql, $arg);

	}

	syslog(LOG_NOTICE, "upload:{$RES->req_ulid} stat:$cre_stat");

	$dbc->update('log_upload', [
		'stat' => $cre_stat,
	], [ 'id' => $RES->req_ulid ]);

	_stat_count('bong_cre_ccrs_upload_rx', 1);
	_stat_count(sprintf('bong_cre_ccrs_upload_rx_%d', $cre_stat), 1);

	// $status = new
	// $status->setPull($cre_stat);

	// Archive
	$csv_name = basename($csv_file);
	rename($csv_file, sprintf('%s/var/ccrs-incoming-done/%s', APP_ROOT, $csv_name));

	return true;
}


/**
 *
 */
function _process_csv_file_b2b_incoming(string $csv_file, string $req_ulid, $csv_pipe, array $csv_head)
{
	global $dbc;

	$cre_stat = 200;
	$idx_line = 0;
	$lic_data = [];
	$update_count = 0;

	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;

		if (count($csv_head) != count($csv_line)) {
			var_dump($csv_head);
			var_dump($csv_line);
			echo "Two Lines Don't Match\n";
			exit(1);
		}

		$csv_line = array_combine($csv_head, $csv_line);

		$lic_data = _license_load_check($dbc, $lic_data['code'], $csv_line['ToLicenseNumber']);
		if (empty($lic_data)) {
			_exit_fail_file_move($csv_file, $csv_line, '!! License Not Found [BCC-957]');
		}

		// Build ID from Hash
		// $b2b_id = md5(sprintf('%s.%s.%s', $csv_line['FromLicenseNumber'], $csv_line['ToLicenseNumber'], $csv_line['TransferDate']));
		// $chk = $dbc->fetchRow('SELECT * FROM b2b_incoming WHERE id = :pk', [ ':pk' => $b2b_id ]);
		// if (empty($chk)) {
		// 	$dbc->insert('b2b_incoming', [
		// 		'id' => $b2b_id,
		// 		'source_license_id' => $csv_line['FromLicenseNumber'],
		// 		'target_license_id' => $RES->license_id,
		// 		'stat' => 100,
		// 		'name' => sprintf('Sold By: %s, Ship To: %s', $csv_line['FromLicenseNumber'], $csv_line['ToLicenseNumber'])
		// 	]);
		// }

		$err = $RES->errorExtractFromLine($csv_line);
		$err_list = $err['data'];

		switch ($err['code']) {
			case 200:
			case 400:
			case 404:
			case 403:
				// Not Authorized on this License
				$cre_stat = $err['code'];
				break;
			default:
				throw new \Exception('Unexpected Status');
		}

		// Result
		if (count($err_list)) {

			$err_line = implode('; ', $err_list);
			$out_line = implode(',', $csv_line);

			// echo sprintf("%04d:%s\n    !!%s\n", $idx_line, $out_line, $err_line);

		}

		// INSERT or UPDATE
		$sql = "UPDATE b2b_incoming_item SET stat = :s1, data = :d1 WHERE id = :pk";
		$arg = [
			':pk' => $csv_line['ExternalIdentifier'],
			':s1' => $cre_stat,
			':d1' => json_encode($rec_data)
			// ':cs' => $cre_stat,
		];
		$chk = $dbc->query($sql, $arg);
		$update_count += $chk;

	}

	$dbc->update('log_upload', [
		'stat' => $cre_stat,
	], [ 'id' => $req_ulid ]);

	// Archive
	$csv_name = basename($csv_file);
	rename($csv_file, sprintf('%s/var/ccrs-incoming-done/%s', APP_ROOT, $csv_name));

	return [
		'req' => $req_ulid,
		'stat' => $cre_stat,
		'update' => $update_count
	];

}

/**
 * Special Case for Manifest Header
 * It's almost always only ONE error line
 * They fold the header-rows of the Maniest.csv into columns
 */
function _process_csv_file_b2b_outgoing_manifest($RES, $csv_file, $csv_pipe, $csv_head)
{
	global $dbc;

	echo "_process_csv_file_b2b_outgoing_manifest($csv_file)\n";

	$cre_stat = 200;
	$idx_line = 0;

	// Try to Find SOmething?
	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;

		if (count($csv_head) != count($csv_line)) {
			var_dump($csv_head);
			var_dump($csv_line);
			echo "Two Lines Don't Match\n";
			exit(1);
		}

		$csv_line = array_combine($csv_head, $csv_line);
		$csv_line['@id'] = $csv_line['ExternalManifestIdentifier'];

		$err = $RES->errorExtractFromLine($csv_line);
		$err_list = $err['data'];

		switch ($err['code']) {
			case 200:
			case 400:
			case 403:
			case 404:

				// Awesome
				$cre_stat = $err['code'];

				// Somekind of Errors
				$sql = <<<SQL
				UPDATE b2b_outgoing
				   SET updated_at = now(),
				   stat = :s1,
				   data = (data || :d1)
				WHERE id = :b0;
				SQL;
				// AND license_id_source ?
				$res = $dbc->query($sql, [
					':b0' => $csv_line['@id'],
					':s1' => $err['code'],
					':d1' => json_encode([
						'@result' => $csv_line
					])
				]);

				if (1 != $res) {
					var_dump($res);
					throw new \Exception(sprintf('Failed to Update B2B Outgoing "%s"', $csv_line['@id']));
				}

				break;

			default:
				var_dump($csv_line);
				var_dump($err);
				var_dump($err_list);
				throw new \Exception('WTF');
		}

	}

	$dbc->update('log_upload', [
		'stat' => $cre_stat,
	], [ 'id' => $RES->req_ulid ]);

	// Archive
	$csv_name = basename($csv_file);
	rename($csv_file, sprintf('%s/var/ccrs-incoming-done/%s', APP_ROOT, $csv_name));

}

/**
 * Special Case for Manifest Detail
 *
 * There is also an odd case where this file shows up but it's empty
 * That is, it's only the header row, and it's name is ManifestHeader*.csv
 * But it's columns are that of ManifestDetail*.csv
 */
function _process_csv_file_b2b_outgoing_manifest_item($RES, $csv_file, $csv_pipe, $csv_head)
{
	global $dbc;

	echo "_process_csv_file_b2b_outgoing_manifest_item($csv_file, \$csv_pipe, \$csv_head)\n";

	$idx_line = 0;

	$b2b_outgoing_id_list = [];

	// Try to Find SOmething?
	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;

		if (count($csv_head) != count($csv_line)) {
			var_dump($csv_head);
			var_dump($csv_line);
			throw new \Exception("Field Count Mis-Match [CCP-1251]");
		}

		$csv_line = array_combine($csv_head, $csv_line);
		$csv_line['@id'] = $csv_line['ExternalIdentifier'];

		$err = $RES->errorExtractFromLine($csv_line);
		$err_list = $err['data'];

		switch ($err['code']) {
			case 200:

				// Awesome
				$res = $dbc->query('UPDATE b2b_outgoing_item SET updated_at = now(), stat = :s1 WHERE id = :b0', [
					':b0' => $b2b_item['id'],
					':s1' => $err['code'],
				]);

				break;

			case 400:
			case 404:

				// Some kind of Errors
				$res = $dbc->query('UPDATE b2b_outgoing_item SET updated_at = now(), stat = :s1, data = (data || :d1) WHERE id = :b0', [
					':b0' => $csv_line['@id'],
					':s1' => $err['code'],
					':d1' => json_encode([
						'@result' => $csv_line
					])
				]);
				if (1 != $res) {
					throw new \Exception(sprintf('Failed to Update B2B Outgoing Item "%s"', $csv_line['@id']));
				}

				break;

			// case 403:
			// 	// Not Authorized on this License
			// 	$lic_dead = true;
			// 	break;
			// case 404:
			// 	// UPDATE fails, needs INSERT
			// 	break;
			default:
				var_dump($csv_line);
				var_dump($err);
				var_dump($err_list);
				throw new \Exception('WTF');
		}

		// Now Lookup That Record and get the B2B ID
		$b2b_item = $dbc->fetchRow('SELECT id, b2b_outgoing_id, stat FROM b2b_outgoing_item WHERE id = :i0', [
			':i0' => $csv_line['@id']
		]);
		if (empty($b2b_item['id'])) {
			throw new \Exception("WHERE IS OUTGOING ITEM?");
		}

		if (empty($b2b_outgoing_id_list[ $b2b_item['b2b_outgoing_id'] ])) {
			$b2b_outgoing_id_list[ $b2b_item['b2b_outgoing_id'] ] = [];
		}
		$b2b_outgoing_id_list[ $b2b_item['b2b_outgoing_id'] ][] = $b2b_item['stat'];

	}

	if (0 == $idx_line) {
		// Nothing?
		echo "NOTHING\n";

	}

	// I think this will only ever be one
	// cause this is the reply for the b2b/outgoing/file
	// var_dump($b2b_outgoing_id_list);

	$cre_stat = 200;
	foreach ($b2b_outgoing_id_list as $b2b_ulid => $b2b_stat) {

		$cre_stat = max($b2b_stat);

		$res = $dbc->query('UPDATE b2b_outgoing SET updated_at = now(), stat = :s1 WHERE id = :b0', [
			':b0' => $b2b_ulid,
			':s1' => $cre_stat,
		]);
		if (1 != $res) {
			throw new \Exception(sprintf('Failed to Update B2B Outgoing "%s"', $b2b_ulid));
		}

	}

	$dbc->update('log_upload', [
		'stat' => $cre_stat,
	], [ 'id' => $RES->req_ulid ]);

	// Archive
	$csv_name = basename($csv_file);
	rename($csv_file, sprintf('%s/var/ccrs-incoming-done/%s', APP_ROOT, $csv_name));

}


/**
 * Move File and Bail
 */
function _exit_fail_file_move($csv_file, $csv_line, $msg)
{
	echo $msg;
	echo "\n";
	var_dump($csv_line);

	$csv_name = basename($csv_file);
	$new_name = sprintf('%s/var/ccrs-incoming-fail/%s', APP_ROOT, $csv_name);
	rename($csv_file, $new_name);

	echo "File Moved: $new_name\n";

	exit(1);

}


/**
 *
 */
function _license_load_check($dbc, $lic0, $lic1) : array
{

	static $lic_data;

	if (empty($lic0)) {

			$lic_code = $lic1;
			$lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

	} elseif ($lic0 != $lic1) {

			// $lic_code = $csv_line['ToLicenseNumber'];
			// $lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);
			$lic_data = [];

			// throw new \Exception('SWITCHING LICENSE [BCC-369]');

	}

	return $lic_data;

}

// Notify the Primary Application
function _notify_app(array $arg)
{
	$url = \OpenTHC\Config::get('openthc/app/origin');
	$url = rtrim($url, '/');
	$url = sprintf('%s/api/v2017/notify', $url);
	$req = _curl_init($url);
	curl_setopt($req, CURLOPT_POST, true);
	curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($arg));

	$res = curl_exec($req);
	return $res;
}
