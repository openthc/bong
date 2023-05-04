#!/usr/bin/php
<?php
/**
 * Process CCRS Incoming Files
 *
 * SPDX-License-Identifier: MIT
 */

use \Edoceo\Radix\DB\SQL;

require_once(__DIR__ . '/../boot.php');

openlog('openthc-bong', LOG_ODELAY | LOG_PERROR | LOG_PID, LOG_LOCAL0);

$dbc = _dbc();
$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));

// The Compliance Engine
$cre = new \OpenTHC\CRE\CCRS([
	'tz' => $tz0,
]);


// Process incoming message queue
$message_file_list = glob(sprintf('%s/var/ccrs-incoming-mail/*.txt', APP_ROOT));
foreach ($message_file_list as $message_file)
{

	echo "message: $message_file\n";

	$message_type = '';

	// Match Filename
	$message_mime = mailparse_msg_parse_file($message_file); // resource
	$message_part_list = mailparse_msg_get_structure($message_mime); // Array
	// echo "message-part-list: " . implode(' / ', $message_part_list) . "\n";

	// $mime_part = mailparse_msg_get_part($message_mime, 0); // resource
	$message_head = mailparse_msg_get_part_data($message_mime);
	$message_body = mailparse_msg_extract_part_file($message_mime, $message_file, null);

	if ( ! empty($message_head['headers']['subject'])) {
		$s = $message_head['headers']['subject'];
		// echo "Subject: {$s}\n";
		if (preg_match('/CCRS error/', $s)) {
			$message_type = 'ccrs-failure-data';
		} elseif (preg_match('/CCRS Processing Error/', $s)) {
			$message_type = 'ccrs-failure-full';
		} elseif (preg_match('/Manifest Generated/', $s)) {
			$message_type = 'b2b-outgoing-manifest';
		}
	}

	// Inflate the parts
	$message_part_data = [];
	foreach ($message_part_list as $p) {
		$mime_part = mailparse_msg_get_part($message_mime, $p); // resource
		$mime_part_data = mailparse_msg_get_part_data($mime_part);
		$message_part_data[$p] = $mime_part_data;
		// mailparse_msg_free($mime_part); // nope, doesn't work
	}

	$output_data = null;
	$output_file = null;

	foreach ($message_part_data as $part_key => $part) {
		// echo "$part_key == {$part['content-type']} : {$part['content-name']}\n";
		if ('application/octet-stream' == $part['content-type']) {
			if (
				preg_match('/^\w+_\w{6,10}_\d+T\d+\.csv$/', $part['content-name'])
				|| preg_match('/^Strain_\d+T\d+\.csv$/', $part['content-name'])
				) {

				// echo "message: {$message['id']}; part: $part_key is file: {$part['content-name']}\n";

				$part_res = mailparse_msg_get_part($message_mime, $part_key);
				$output_data = mailparse_msg_extract_part_file($part_res, $message_file, null);
				$output_file = sprintf('%s/var/ccrs-incoming/%s', APP_ROOT, $part['content-name']);
				$output_size = file_put_contents($output_file, $output_data);

				break; // foreach

			} elseif (preg_match('/^Manifest_(.+)_(\w+)\.pdf$/', $part['content-name'])) {
				// It's the Manifest PDF

				$part_res = mailparse_msg_get_part($message_mime, $part_key);
				$output_data = mailparse_msg_extract_part_file($part_res, $message_file, null);
				$output_file = sprintf('%s/var/ccrs-incoming/%s', APP_ROOT, $part['content-name']);
				$output_size = file_put_contents($output_file, $output_data);

				break; // foreach
			}
		}
	}

	mailparse_msg_free($message_mime);

	switch ($message_type) {
		case 'ccrs-failure-full':
			// NOthing To do?  What?
			_ccrs_pull_failure_full($message_file, $message_head, $message_body, $output_file);
			break;
		case 'ccrs-failure-data':
			_ccrs_pull_failure_data($message_file, $output_file);
			break;
		case 'b2b-outgoing-manifest':
			_ccrs_pull_manifest_file($message_file, $output_file);
			break;
	}

}

// Cleanup Legacy Data Files
$file_list = glob(sprintf('%s/var/ccrs-incoming/*.csv', APP_ROOT));
foreach ($file_list as $file) {

	// Patch Text Errors? (see ccrs-incoming in OPS)
	_csv_file_patch($file);
	_csv_file_incoming(null, $file);

}

$dt0 = new \DateTime();
$rdb = \OpenTHC\Service\Redis::factory();
$rdb->hset('/cre/ccrs', 'pull/time', $dt0->format(\DateTimeInterface::RFC3339));


/**
 *
 */
function _ccrs_pull_failure_data(string $message_file, string $output_file) : int
{
	if (empty($output_file)) {
		// Failed to Parse Output File
		echo "Failed to Process: $message_file\n";
		exit(1);
		$message_file_fail = sprintf('%s/var/ccrs-incoming-fail/%s', APP_ROOT, basename($message_file));
		rename($message_file, $message_file_fail);
		return 0;
	}

	_csv_file_patch($output_file);
	_csv_file_incoming($message_file, $output_file);

/*
	$message_full = file_get_contents($message_file);
	$message_text = strip_tags($message_body);

	// @note do we need the .csv file extension?
	// Sale_AF0E72B77C_20230307T170533169.
	if (preg_match('/Errors have occurred in file: (\w+)_\w+_(\w+)/', $message_body, $m)) {
		$tab_name = $m[1];
		$req_ulid = $m[2];
		// $res_time = $m[3];

		$dt0 = new \DateTime('now', $tz0);
		try {
			// Sometimes their time thing is bullshit
			// https://github.com/openthc/ccrs/issues/44
			$dt1 = new \DateTime($res_time, $tz0);
		} catch (\Exception $e) {
			// Ignore
			// @todo /mbw 2023-074
			$dt1 = new \DateTime($message_head['headers']['date'], $tz0);
		}
	}

	$sql = <<<SQL
	UPDATE log_upload SET stat = 400, result_data = coalesce(result_data, '{}'::jsonb) || :d0
	WHERE id = :r0
	SQL;

	$res = $dbc->query($sql, [
		':r0' => $req_ulid,
		':d0' => json_encode([
			'type' => '',
			'data' => '',
			'@result' => [
				'type' => 'mail',
				'data' => $message_full,
				'created_at' => $dt0->format(\DateTime::RFC3339),
				'created_at_cre' => $dt1->format(\DateTime::RFC3339)
			],
		]),
	]);
	if (1 != $res) {
		throw new \Exception('Cannot find in Upload Log');
	}
*/

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

	return 1;
}

/**
 * Process Full Failure
 * The CSV "has not been processed"
 */
function _ccrs_pull_failure_full(string $message_file, array $message_head, string $message_body, string $output_file) : int
{
	global $dbc, $tz0;

	$message_full = file_get_contents($message_file);
	$message_text = strip_tags($message_body);

	if (preg_match('/The file (\w+)_\w+_(\w+)_(\w+)\.csv/', $message_body, $m)) {

		$tab_name = $m[1];
		$req_ulid = $m[2];
		$res_time = $m[3];

		$dt0 = new \DateTime('now', $tz0);
		// $dt1 = clone $dt0;
		try {
			// Sometimes their time thing is bullshit
			// https://github.com/openthc/ccrs/issues/44
			$dt1 = new \DateTime($res_time, $tz0);
			// var_dump($dt1);
		} catch (\Exception $e) {
			// Ignore
			$dt1 = new \DateTime($message_head['headers']['date'], $tz0);
			// var_dump($dt1);
		}

		$sql = <<<SQL
		UPDATE log_upload SET stat = 400, result_data = coalesce(result_data, '{}'::jsonb) || :d0
		WHERE id = :r0
		SQL;

		$res = $dbc->query($sql, [
			':r0' => $req_ulid,
			':d0' => json_encode([
				'type' => '',
				'data' => '',
				'@result' => [
					'type' => 'mail',
					'data' => $message_full,
					'created_at' => $dt0->format(\DateTime::RFC3339),
					'created_at_cre' => $dt1->format(\DateTime::RFC3339)
				]
			])
		]);
		if (1 != $res) {
			throw new \Exception('Cannot find in Upload Log');
		}

		$chk = $dbc->fetchRow('SELECT id, license_id, name FROM log_upload WHERE id = :r0', [ ':r0' => $req_ulid ]);

		$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
		if ( ! rename($message_file, $message_file_done)) {
			throw new \Exception("Cannot archive incoming email");
		}

		return 0;
	}

	var_dump($message_head);
	var_dump($message_text);

	echo "FAILURE 207\n";

	exit(0);

	$message_file_fail = sprintf('%s/var/ccrs-incoming-fail/%s', APP_ROOT, basename($message_file));

	if ( ! rename($message_file, $message_file_fail)) {
		throw new \Exception("Cannot archive incoming email");
	}

	echo "FAIL $message_file => $message_file_fail\n";

	return 1;

}

/**
 * Pull the Manifest PDF File into BONG
 */
function _ccrs_pull_manifest_file(string $message_file, string $output_file) : int
{
	global $dbc;

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
		echo "Failed to Process: $message_file; Missing B2B Outgoing [CCP-186]\n";
		exit(1);
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

	// Notify the Primary Application
	$url = \OpenTHC\Config::get('openthc/app/base');
	$url = 'https://app.djb.openthc.dev/';
	$url = rtrim($url, '/');
	$url = sprintf('%s/api/v2017/notify', $url);
	$req = _curl_init($url);
	curl_setopt($req, CURLOPT_POST, true);
	curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query([
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
	]));

	$res = curl_exec($req);
	$inf = curl_getinfo($req);
	if (200 != $inf['http_code']) {
		var_dump($res);
		throw new \Exception('HTTP Request Failed');
		exit;
	}

	// Update b2b_outgoing with Stat 200?
	// Still Need to do the B2B_Outgoing (Sales) Upload
	$sql = 'UPDATE b2b_outgoing SET stat = 200 WHERE id = :b2b0';
	$arg = [ ':b2b0' => $b2b_outgoing['id'] ];
	$dbc->query($sql, $arg);

	// How to Find the CCRS Upload to Re-Map?
	$message_data = file_get_contents($message_file);
	if (preg_match('/Manifest_\w+_(\w+)_\d+T\d+\.csv/', $message_data, $m)) {

		$req_ulid = $m[1];

		$dbc->query('UPDATE log_upload SET stat = 202 WHERE id = :pk', [
			':pk' => $req_ulid,
		]);

		// $log_data = $dbc->fetchRow('SELECT id, license_id, result_data FROM log_upload WHERE id = :u0', [ ':u0' => $req_ulid ]);
		// if (empty($log_data)) {
		// 	throw new \Exception('Lost Log Audit');
		// 	// echo "!! NO LOG\n";
		// 	// INSERT RESPONSE THO?
		// }


		// $result_data = json_decode($log_data['result_data'], true);
		// if (empty($result_data)) {
		// 	$result_data = [];
		// }

		// $result_data['@result-file'] = [
		// 	'name' => basename($output_file),
		// 	'data' => file_get_contents($output_file),
		// 	// 'created_at_cre' => $csv_time->format(\DateTimeInterface::RFC3339),
		// ];
		// if ( ! empty($message_file)) {
		// 	$result_data['@result-mail'] = file_get_contents($message_file);
		// }
		// $update = [
		// 	'stat' => 200,
		// 	'result_data' => json_encode($result_data),
		// 	'updated_at' => $csv_time->format(\DateTimeInterface::RFC3339),
		// ];
		// $dbc->update('log_upload', $update, [ 'id' => $req_ulid ]);

	}

	// Archive File
	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

	unlink($output_file);

	return 1;

}


/**
 * Patch bullshit we find in these files
 */
function _csv_file_patch(string $csv_file) : void
{
	// Patch the WHOLE BLOB
	$csv_data = file_get_contents($csv_file);

	// Fix some bullshit they put in the CSVs (Bug #38)
	$csv_data = str_replace('Insert, Update or Delete', 'INSERT UPDATE or DELETE', $csv_data);
	// $part_body = str_replace('Operation is invalid must be Insert,  Update or Delete'
	// 	, 'Operation is invalid must be INSERT UPDATE or DELETE'
	// 	, $part_body);

	// This one always goes "comma space space CheckSum"
	// $part_body = str_replace(',  CheckSum and', ': CheckSum and', $part_body);
	// $part_body = preg_replace('/found, CheckSum/i', 'found: CheckSum', $part_body);

	// words, comma spaces? "Checksum and"
	$csv_data = preg_replace('/(\w+),\s+CheckSum and/', '$1: CheckSum and', $csv_data);

	file_put_contents($csv_file, $csv_data);

}


/**
 *
 */
function _csv_file_incoming(?string $source_mail, string $csv_file) : bool
{
	global $cre, $dbc, $tz0;

	$dt0 = new \DateTime('now', $tz0);

	echo "_csv_file_incoming($csv_file)\n";

	$cre_stat = 200;
	$lic_code = null;
	$lic_data = [];
	$lic_dead = false;

	$csv_time = $cre->csv_file_date($csv_file);

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
		case 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage,UnitWeightGrams':
			$tab_name = 'product';
			break;
		case 'Strain,StrainType,CreatedBy,CreatedDate,ErrorMessage': // v0
			// @todo this one needs special processing ?
			$csv_pkid = 'Strain';
			$tab_name = 'variety';
			// $lic_code = '018NY6XC00L1CENSE000000000';
			// $lic_data = [
			// 	'id' => '018NY6XC00L1CENSE000000000',
			// 	'name' => '-system-',
			// 	'code' => '-system-',
			// ];
			break;
		case 'LicenseNumber,Strain,CreatedBy,CreatedDate,StrainType,ErrorMessage': // v1
			$csv_pkid = 'Strain';
			$tab_name = 'variety';
			break;
		case 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,SalesTax,OtherTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_outgoing_item';
			break;
		case 'Submittedby,SubmittedDate,NumberRecords,ExternalManifestIdentifier,HeaderOperation,TransportationType,OriginLicenseNumber,OriginLicenseePhone,OriginLicenseeEmailAddress,TransportationLicenseNumber,DriverName,DepartureDateTime,ArrivalDateTime,VIN#,VehiclePlateNumber,VehicleModel,VehicleMake,VehicleColor,DestinationLicenseNumber,DestinationLicenseePhone,DestinationLicenseeEmailAddress,ErrorMessage':
			$tab_name = 'b2b_outgoing_manifest';
			_process_csv_file_b2b_outgoing_manifest($csv_file, $csv_pipe, $csv_head);
			return false;
			break;
		case 'InventoryExternalIdentifier,PlantExternalIdentifier,Quantity,UOM,WeightPerUnit,ServingsPerUnit,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_outgoing_manifest_line_item';
			_process_csv_file_b2b_outgoing_manifest_item($csv_file, $csv_pipe, $csv_head);
			return false;
			break;
		default:
			throw new Exception("CSV Header Not Handled\n$tab_name");
			break;
	}

	// Canary Line
	$csv_line = fgetcsv($csv_pipe);
	$idx_line++;

	// It's our canary line
	$req_ulid = '';
	$csv_line_text = implode(',', $csv_line);
	if (preg_match('/(\w+ UPLOAD.+(01\w{24})).+\-canary\-/', $csv_line_text, $m)) {
		$req_ulid = $m[2];
	} elseif (preg_match('/(\w+ UPLOAD.+(01\w{24}))/', $csv_line_text, $m)) {
		$req_ulid = $m[2];
	} else {
		echo "Canary??:  $csv_line_text\n";
		echo "Need to Parse This One\n";
		exit(1);
	}

	// Stash Result Data
	$log_data = $dbc->fetchRow('SELECT id, license_id, result_data, res_info FROM log_upload WHERE id = :u0', [ ':u0' => $req_ulid ]);
	if (empty($log_data)) {
		echo "!! NO LOG\n";
		// INSERT RESPONSE THO?
	} else {

		$res_info = json_decode($log_data['res_info'], true);
		$result_data = json_decode($log_data['result_data'], true);
		if (empty($result_data)) {
			$result_data = [];
		}

		$res_info['file'] = basename($csv_file);
		$res_info['size'] = strlen(file_get_contents($csv_file));

		$result_data['@result-file'] = [
			'name' => basename($csv_file),
			'data' => file_get_contents($csv_file),
			'meta' => [
				'created_at' => $dt0->format(\DateTime::RFC3339),
				'created_at_cre' => $csv_time->format(\DateTimeInterface::RFC3339),
			]
		];
		if ( ! empty($source_mail)) {
			$result_data['@result-mail'] = file_get_contents($source_mail);
		}
		$update = [
			'res_info' => json_encode($res_info),
			'result_data' => json_encode($result_data),
			'updated_at' => $csv_time->format(\DateTimeInterface::RFC3339),
		];
		$dbc->update('log_upload', $update, [ 'id' => $req_ulid ]);
	}

	// Some
	$b2b_incoming_item_list = [];
	$b2b_outgoing_item_list = [];

	// Spin the CSV File
	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;

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
				$csv_line['LicenseNumber'] = $csv_line['FromLicenseNumber'];
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

		// License Map Because CCRS Truncates
		switch ($csv_line['LicenseNumber']) {
			case '739766888':
				$csv_line['LicenseNumber'] = '7397668881';
				break;
			case '':
				break;
		}

		// Discover License
		if (empty($lic_code)) {

			$lic_code = $csv_line['LicenseNumber'];
			$lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

		} elseif ($lic_code != $csv_line['LicenseNumber']) {

			throw new \Exception('SWITCHING LICENSE [BCC-218]');

		}

		if (empty($lic_data['id'])) {
			var_dump($lic_code);
			_exit_fail_file_move($csv_file, $csv_line, '!! License Not Found [BCC-665]');
		}

		$err = _process_err_list($csv_line);
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

				$b2b_incoming_item_list[] = $csv_line['@id'];

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

				$b2b_outgoing_item_list[] = $csv_line['@id'];

				break;

			default:
				$rec_data = $dbc->fetchOne("SELECT data FROM {$tab_name} WHERE license_id = :l0 AND id = :pk", [
					':l0' => $lic_data['id'],
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
				if (1 != $chk) {
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
					':l0' => $lic_data['id'],
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

			// $b2b_incoming_item_list
			// var_dump($b2b_incoming_item_list);
			// exit;

			// var_dump($err_list);
			// var_dump($rec_data);
			// exit;

			syslog(LOG_WARNING, "upload:$req_ulid b2b-incoming:$lic_code; stat:??? cre_stat=$cre_stat");

			$sql = <<<SQL
			UPDATE b2b_incoming
			SET stat = (SELECT coalesce(max(stat), 100) FROM b2b_incoming_item WHERE b2b_incoming_item.b2b_incoming_id = b2b_incoming.id)
			WHERE b2b_incoming.target_license_id = :l0 AND b2b_incoming.stat != 202
			SQL;

			$ret = $dbc->query($sql, [ ':l0' => $lic_data['id'] ]);

			echo "UPDATED B2B INCOMING: $ret\n";

			break;

		case 'b2b_outgoing_item':

			syslog(LOG_WARNING, "upload:$req_ulid b2b-outgoing:$lic_code; stat:??? cre_stat=$cre_stat");

			// $sql = <<<SQL
			// UPDATE b2b_outgoing
			// SET stat = (SELECT max(coalesce(stat, $cre_stat)) FROM b2b_outgoing_item WHERE b2b_outgoing_item.b2b_outgoing_id = b2b_outgoing.id)
			// WHERE source_license_id = :l0
			// SQL;

			// $ret = $dbc->query($sql, [ ':l0' => $lic_data['id'] ]);

			// var_dump($ret);

			break;
	}

	// Only if a License as Set
	if ( ! empty($lic_data['id']) ) {

		if ($lic_dead) {

			syslog(LOG_WARNING, "upload:$req_ulid license:$lic_code; stat:403");
			// $license_stat_list1[ $lic_code ]['flag1'] = ($license_stat_list1[ $lic_code ]['flag'] & ~LICENSE_FLAG_CRE_HAVE);
			// $license_stat_list1[ $lic_code ]['stat1'] = 403;
			$sql = 'UPDATE license SET stat = 403 WHERE id = :l0 AND stat != 403';
			$arg = [
				':l0' => $lic_data['id']
			];
			$dbc->query($sql, $arg);

			// Update the Other License Records
			$sql = 'UPDATE variety SET stat = 403 WHERE license_id = :l0 AND stat != 403';
			$dbc->query($sql, $arg);

			// Update the Other License Records
			$sql = 'UPDATE section SET stat = 403 WHERE license_id = :l0 AND stat != 403';
			$dbc->query($sql, $arg);

			// Update the Other License Records
			$sql = 'UPDATE product SET stat = 403 WHERE license_id = :l0 AND stat != 403';
			$dbc->query($sql, $arg);

		} else {

			$sql = 'UPDATE license SET stat = 200 WHERE id = :l0 AND stat != 200';
			$arg = [
				':l0' => $lic_data['id']
			];
			$dbc->query($sql, $arg);

		}

	} elseif ($idx_line > 2) {
		var_dump($idx_line);
		var_dump($idx_line);
		throw new \Exception("Many Lines, No License Detected?!?");
	}

	syslog(LOG_NOTICE, "upload:$req_ulid stat:$cre_stat");

	$dbc->update('log_upload', [
		'stat' => $cre_stat,
	], [ 'id' => $req_ulid ]);

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
		// 		'target_license_id' => $lic_data['id'],
		// 		'stat' => 100,
		// 		'name' => sprintf('Sold By: %s, Ship To: %s', $csv_line['FromLicenseNumber'], $csv_line['ToLicenseNumber'])
		// 	]);
		// }

		$err = _process_err_list($csv_line);
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
function _process_csv_file_b2b_outgoing_manifest($csv_file, $csv_pipe, $csv_head)
{
	global $dbc;

	echo "_process_csv_file_b2b_outgoing_manifest($csv_file)\n";

	$cre_stat = 200;
	$idx_line = 0;
	$req_ulid = null;

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
		// var_dump($csv_line);
		if (preg_match('/code\+(\w{26})@openthc.com/', $csv_line['OriginLicenseeEmailAddress'], $m)) {
			$csv_ulid = $m[1];
			if (empty($req_ulid)) {
				$req_ulid = $csv_ulid;
			} else {
				if ($req_ulid != $csv_ulid) {
					throw new \Exception('Switching Request in ManifestHeader!');
				}
			}
		}


		$err = _process_err_list($csv_line);
		$err_list = $err['data'];

		switch ($err['code']) {
			case 200:
			case 400:
			case 403:
			case 404:

				// Awesome
				$cre_stat = $err['code'];

				// Somekind of Errors
				$res = $dbc->query('UPDATE b2b_outgoing SET updated_at = now(), stat = :s1, data = (data || :d1) WHERE id = :b0', [
					':b0' => $csv_line['@id'],
					':s1' => $err['code'],
					':d1' => json_encode([
						'@result' => $csv_line
					])
				]);

				if (1 != $res) {
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

	if ( ! empty($req_ulid)) {
		$dbc->update('log_upload', [
			'stat' => $cre_stat,
		], [ 'id' => $req_ulid ]);
	}

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
function _process_csv_file_b2b_outgoing_manifest_item($csv_file, $csv_pipe, $csv_head)
{
	global $dbc;

	echo "_process_csv_file_b2b_outgoing_manifest_item($csv_file, \$csv_pipe, \$csv_head)\n";

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
		$csv_line['@id'] = $csv_line['ExternalIdentifier'];

		$err = _process_err_list($csv_line);
		$err_list = $err['data'];

		switch ($err['code']) {
			case 200:
				// Awesome
				break;
			case 400:
			case 404:

				$b2b_item = $dbc->fetchRow('SELECT id, b2b_outgoing_id FROM b2b_outgoing_item WHERE id = :i0', [
					':i0' => $csv_line['@id']
				]);

				if (empty($b2b_item['id'])) {
					throw new \Exception("WHERE IS OUTOING ITEM?");
				}

				// Somekind of Errors
				$res = $dbc->query('UPDATE b2b_outgoing_item SET updated_at = now(), stat = :s1, data = (data || :d1) WHERE id = :b0', [
					':b0' => $b2b_item['id'],
					':s1' => $err['code'],
					':d1' => json_encode([
						'@result' => $csv_line
					])
				]);

				if (1 != $res) {
					throw new \Exception(sprintf('Failed to Update B2B Outgoing Item "%s"', $csv_line['@id']));
				}

				$res = $dbc->query('UPDATE b2b_outgoing SET updated_at = now(), stat = :s1 WHERE id = :b0', [
					':b0' => $b2b_item['b2b_outgoing_id'],
					':s1' => $err['code'],
				]);
				if (1 != $res) {
					throw new \Exception(sprintf('Failed to Update B2B Outgoing "%s"', $b2b_item['b2b_outgoing_id']));
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

	}

	if (0 == $idx_line) {
		// Nothing?
		echo "NOTHING\n";

	}

	// exit(0);

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


/**
 *
 */
function _process_err_list($csv_line)
{
	$err_return_code = 200;
	$err_return_list = [];

	$err_source_list = explode(':', $csv_line['ErrorMessage']);
	foreach ($err_source_list as $err_text) {
		$err_text = trim($err_text);
		switch ($err_text) {
			case 'Area is required':
			case 'Area name is over 75 characters':
			case 'CreatedDate must be a date':
			case 'DestinationLicenseeEmailAddress is required':
			case 'DestinationLicenseePhone is required':
			case 'DestinationLicenseePhone must not exceed 14 characters':
			case 'DestinationLicenseNumber must be numeric':
			case 'DriverName is required':
			case 'ExternalIdentifier is required':
			case 'FromInventoryExternalIdentifier is required':
			case 'FromLicenseNumber is required':
			case 'FromLicenseNumber must be numeric':
			case 'HarvestDate must be a date':
			case 'InitialQuantity is required':
			case 'InitialQuantity must be numeric':
			case 'Invalid Adjustment Reason':
			case 'Invalid Area':
			case 'Invalid DestinationLicenseeEmailAddress':
			case 'Invalid DestinationLicenseNumber':
			case 'Invalid Details Operation':
			case 'Invalid From LicenseNumber':
			case 'Invalid FromInventoryExternalIdentifier':
			case 'Invalid InventoryCategory/InventoryType combination':
			case 'Invalid InventoryExternalIdentifier':
			case 'Invalid LicenseeID':
			case 'Invalid NumberRecords':
			case 'Invalid OriginLicenseeEmailAddress':
			case 'Invalid OriginLicenseNumber':
			case 'Invalid Product':
			case 'Invalid Sale':
			case 'Invalid Strain Type':
			case 'Invalid Strain':
			case 'Invalid To InventoryExternalIdentifier':
			case 'Invalid To LicenseNumber':
			case 'Invalid ToInventoryExternalIdentifier':
			case 'Invalid UOM':
			case 'Invalid VehicleColor':
			case 'Invalid VehicleMake':
			case 'Invalid VehiclePlateNumber':
			case 'InventoryCategory is required':
			case 'InventoryExternalIdentifier or PlantExternalIdentifier is required':
			case 'InventoryType is required':
			case 'IsMedical must be True or False':
			case 'LicenseNumber is required':
			case 'LicenseNumber must be numeric':
			case 'Name is over 50 characters': // Variety
			case 'Name is over 75 characters':
			case 'Name is required':
			case 'Operation is invalid must be INSERT UPDATE or DELETE':
			case 'OriginLicenseePhone is required':
			case 'OriginLicenseePhone must not exceed 14 characters':
			case 'OriginLicenseNumber must be numeric':
			case 'Product is required':
			case 'Quantity is required':
			case 'Quantity must be numeric':
			case 'QuantityOnHand must be numeric':
			case 'SaleDetailExternalIdentifier is required':
			case 'SaleExternalIdentifier is required':
			case 'SoldToLicenseNumber required for wholesale':
			case 'Strain is required':
			case 'Strain Name reported is not linked to the license number. Please ensure the strain being reported belongs to the licensee':
			case 'ToInventoryExternalIdentifier is required':
			case 'ToLicenseNumber is required':
			case 'ToLicenseNumber must be numeric':
			case 'TotalCost must be numeric':
			case 'UnitPrice is required':
			case 'UnitPrice must be numeric':
			case 'UpdatedDate is required for Update or Delete Operations':
			case 'VehicleColor is required':
			case 'VehicleMake is required':
			case 'VehicleModel is required':
			case 'VehiclePlateNumber is required':
			case 'VINNumber is required':
				$err_return_list[] = $err_text;
				break;
			case 'CheckSum and number of records don\'t match':
				// This one is more of a warning but it shows up for every line
				// if the line count NumberRecords field is wrong
				// So, we just ignore it
				break;
			case 'Duplicate External Identifier':
			case 'Duplicate ExternalManifestIdentifier':
			case 'Duplicate Sale for Licensee':
			case 'Duplicate Strain/StrainType':
				// Cool, this generally means everything is OK
				// BUT!! It could mean a conflict of IDs -- like if the object wasn't for the same license?
				// if ('INSERT' == strtoupper($csv_line['Operation'])) {
				// 	$cre_stat = 200;
				// }
				break;
			case 'Duplicate Strain. The Strain must be unique for the LicenseNumber':
				// Special Case on Variety -- Give a 202, not 200
				return [
					'code' => 202,
					'data' => [],
				];
				break;
			case 'Integrator is not authoritzed to update licensee':
			case 'Integrator is not authorized to update licensee':
			case 'OriginLicenseNumber is not assigned to Integrator':
			case 'License Number is not assigned to Integrator':
			case 'LicenseNumber is not assigned to Integrator':
				return [
					'code' => 403,
					'data' => [ $err_text ],
				];
				break;
			case 'ExternalIdentifier not found':
			case 'ExternalManifestIdentifier does not exist in CCRS. Cannot Update or Delete':
			case 'Invalid SaleDetail':
			case 'SaleDetailExternalIdentifier not found':
			case 'SaleExternalIdentifier not found':
				return [
					'code' => 404,
					'data' => [ $err_text ],
				];
				break;
			default:
				var_dump($csv_line);
				echo "Unexpected Error: '$err_text'\nLINE: '{$csv_line['ErrorMessage']}'\n";
				exit(1);
		}
	}

	if (count($err_return_list)) {
		return [
			'code' => 400,
			'data' => $err_return_list
		];
	}

	return [
		'code' => 200,
		'data' => [],
	];

}

function _notify_app(array $arg)
{
	// Notify the Primary Application
	$url = \OpenTHC\Config::get('openthc/app/base');
	$url = 'https://app.djb.openthc.dev/';
	$url = rtrim($url, '/');
	$url = sprintf('%s/api/v2017/notify', $url);
	$req = _curl_init($url);
	curl_setopt($req, CURLOPT_POST, true);
	curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($arg));

	$res = curl_exec($req);
	return $res;
}
