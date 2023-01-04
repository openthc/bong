#!/usr/bin/php
<?php
/**
 * Process CCRS Incoming Files
 *
 * SPDX-License-Identifier: MIT
 */

use \Edoceo\Radix\DB\SQL;

require_once(__DIR__ . '/../boot.php');

$dbc = _dbc();

// Process incoming message queue
$message_file_list = glob(sprintf('%s/var/ccrs-incoming-mail/*.txt', APP_ROOT));
foreach ($message_file_list as $message_file)
{

	echo "message:$message_file\n";

	$message_type = '';

	// Match Filename
	$message_mime = mailparse_msg_parse_file($message_file); // resource
	$message_part_list = mailparse_msg_get_structure($message_mime); // Array
	// echo "message-part-list: " . implode(' / ', $message_part_list) . "\n";

	// $mime_part = mailparse_msg_get_part($message_mime, 0); // resource
	$message_head = mailparse_msg_get_part_data($message_mime);
	if ( ! empty($message_head['headers']['subject'])) {
		$s = $message_head['headers']['subject'];
		echo "Subject: {$s}\n";
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
			_ccrs_pull_failure_full($message_file, $output_file);
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


/**
 *
 */
function _ccrs_pull_failure_data($message_file, $output_file)
{
	if (empty($output_file)) {
		// Failed to Parse Output File
		echo "Failed to Process: $message_file\n";
		exit(1);
		$message_file_fail = sprintf('%s/var/ccrs-incoming-fail/%s', APP_ROOT, basename($message_file));
		rename($message_file, $message_file_fail);
		return(0);
	}

	_csv_file_patch($output_file);
	_csv_file_incoming($message_file, $output_file);

	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

}

/**
 * Process Full Failure
 */
function _ccrs_pull_failure_full($message_file, $output_file)
{
	$message_file_fail = sprintf('%s/var/ccrs-incoming-fail/%s', APP_ROOT, basename($message_file));

	if ( ! rename($message_file, $message_file_fail)) {
		throw new \Exception("Cannot archive incoming email");
	}

	echo "FAIL $message_file => $message_file_fail\n";

}

/**
 * Pull the Manifest File into BONG
 */
function _ccrs_pull_manifest_file($message_file, $output_file)
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

	$b2b_outgoing_id = $dbc->fetchOne('SELECT id FROM b2b_outgoing WHERE id = :m0', [
		':m0' => $manifest_id
	]);
	if (empty($b2b_outgoing_id)) {
		echo "Failed to Process: $message_file; Missing B2B Outgoing [CCP-186]\n";
		exit(1);
	}

	$sql = <<<SQL
	INSERT INTO b2b_outgoing_file (id, name, body) VALUES (:b2b0, :n1, :b1)
	ON CONFLICT (id) DO
	UPDATE SET name = EXCLUDED.name, body = EXCLUDED.body
	SQL;

	$cmd = $dbc->prepare($sql, null);
	$cmd->bindParam(':b2b0', $b2b_outgoing_id);
	$cmd->bindParam(':n1', basename($output_file));
	$cmd->bindParam(':b1', file_get_contents($output_file), \PDO::PARAM_LOB);
	$cmd->execute();

	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

	unlink($output_file);

}


/**
 * Patch bullshit we find in these files
 */
function _csv_file_patch($csv_file)
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
function _csv_file_incoming($source_mail, $csv_file)
{
	global $dbc;

	echo "_process_csv_file($csv_file)\n";

	$cre_stat = 200;
	$lic_code = null;
	$lic_dead = false;

	/**
	 * error-response-file from the LCB sometimes are missing the
	 * milliseconds portion of the time in the file name
	 * So we have to patch it so it parses the same as their "normal"
	 */
	$csv_time = preg_match('/(\w+_)?\w+_(\d+T\d+)\.csv/i', $csv_file, $m) ? $m[2] : null;
	if (strlen($csv_time) == 15) {
		$csv_time = $csv_time . '000';
	}

	$csv_time = DateTime::createFromFormat('Ymd\TGisv', $csv_time, new DateTimeZone('America/Los_Angeles'));

	// Need to actually keep file name to understand the ?
	$csv_pipe = fopen($csv_file, 'r');
	$idx_line = 1;
	$csv_head = fgetcsv($csv_pipe);
	$csv_head = array_values($csv_head);
	$row_size = count($csv_head);

	$csv_pkid = 'ExternalIdentifier';

	// Assemble Header Line to determine Type
	$tab_name = implode(',', $csv_head);
	switch ($tab_name) {
		case 'FromLicenseNumber,ToLicenseNumber,FromInventoryExternalIdentifier,ToInventoryExternalIdentifier,Quantity,TransferDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_incoming';
			$tab_name = 'b2b_sale_item';
			break;
		case 'LicenseNumber,Name,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage': // v2021-340
		case 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage': // v2022-343
			// Section is also used for the PING test
			$tab_name = 'section';
			break;
		case 'CreatedBy,CreatedDate,ErrorMessage,LicenseNumber,ExternalIdentifier,UpdatedBy,UpdatedDate,Operation,Area,Strain,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,InventoryIdentifier':
			$tab_name = 'lot';
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
			// return _csv_file_incoming_variety($csv_file, $csv_pipe, $csv_head);
			$csv_pkid = 'Strain';
			$tab_name = 'variety';
			$lic_code = '018NY6XC00L1CENSE000000000';
			$lic_data = [
				'id' => '018NY6XC00L1CENSE000000000',
				'name' => '-system-',
				'code' => '-system-',
			];
			break;
		case 'LicenseNumber,Strain,CreatedBy,CreatedDate,StrainType,ErrorMessage': // v1
			$csv_pkid = 'Strain';
			$tab_name = 'variety';
			break;
		case 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,SalesTax,OtherTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_outgoing';
			break;
		case 'Submittedby,SubmittedDate,NumberRecords,ExternalManifestIdentifier,HeaderOperation,TransportationType,OriginLicenseNumber,OriginLicenseePhone,OriginLicenseeEmailAddress,TransportationLicenseNumber,DriverName,DepartureDateTime,ArrivalDateTime,VIN#,VehiclePlateNumber,VehicleModel,VehicleMake,VehicleColor,DestinationLicenseNumber,DestinationLicenseePhone,DestinationLicenseeEmailAddress,ErrorMessage':
			$tab_name = 'b2b_manifest';
			// throw new Exception("File '$tab_name' Not Implemented");
			return false;
			break;
		case 'InventoryExternalIdentifier,PlantExternalIdentifier,Quantity,UOM,WeightPerUnit,ServingsPerUnit,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'b2b_manifest_line_item';
			// throw new Exception("File '$tab_name' Not Implemented");
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
	$chk = $dbc->fetchRow('SELECT id, result_data FROM log_upload WHERE id = :u0', [ ':u0' => $req_ulid ]);
	if (empty($chk)) {
		echo "!! NO LOG\n";
		// INSERT RESPONSE THO?
	} else {

		$result_data = json_decode($chk['result_data'], true);
		if (empty($result_data)) {
			$result_data = [];
		}

		$result_data['@result-file'] = [
			'file' => basename($csv_file),
			'data' => file_get_contents($csv_file)
		];
		if ( ! empty($source_mail)) {
			$result_data['@result-mail'] = file_get_contents($source_mail);
		}
		$update = [
			'result_data' => json_encode($result_data),
			'updated_at' => $csv_time->format(\DateTimeInterface::RFC3339),
		];
		$dbc->update('log_upload', $update, [ 'id' => $req_ulid ]);
	}

	// Should spin the whole file once to verify all the good lines
	// Then spin a second time

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
			case 'section':
				if (empty($csv_line['Name'])) {
					$csv_line['Name'] = $csv_line['Area'];
				}
				break;
			case 'variety':
				if (empty($csv_line['LicenseNumber'])) {
					$csv_line['LicenseNumber'] = '018NY6XC00L1CENSE000000000';
				}
				break;
		}

		if (empty($csv_line['LicenseNumber'])) {
			continue;
		}

		// Discover License
		if (empty($lic_code)) {

			$lic_code = $csv_line['LicenseNumber'];
			$lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

		} elseif ($lic_code != $csv_line['LicenseNumber']) {

			// $lic_code = $csv_line['LicenseNumber'];
			// $lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

			throw new \Exception('SWITCHING LICENSE [BCC-218]');

		}

		if (empty($lic_data)) {
			_exit_fail_file_move($csv_file, $csv_line, '!! License Not Found');
		}

		$err = _process_err_list($csv_line);
		$err_list = $err['data'];

		switch ($err['code']) {
			case 200:
				// Awesome
				break;
			case 400:
				// Somekind of Errors
				$cre_stat = 400;
				break;
			case 403:
				// Not Authorized on this License
				$cre_stat = 403;
				$lic_dead = true;
				break;
			case 404:
				// UPDATE fails, needs INSERT
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

			echo sprintf("%04d:%s\n    !!%s\n", $idx_line, $out_line, $err_line);

		}

		// Inflate the Old Data
		$rec_data = $dbc->fetchOne("SELECT data FROM {$tab_name} WHERE id = :pk", [
			':pk' => $csv_line['@id']
		]);
		if (empty($rec_data)) {
			// Fake It
			$rec_data = [
				'@source' => $csv_line,
			];
		} else {
			$rec_data = json_decode($rec_data, true);
		}

		$rec_data['@result'] = $err;

		$sql = "UPDATE {$tab_name} SET flag = :f1::int, stat = :s1, data = :d1, updated_at = now() WHERE id = :pk";
		$arg = [
			':pk' => $csv_line['@id'],
			':f1' => 0, // $cre_flag,
			':s1' => $err['code'],
			':d1' => json_encode($rec_data)
		];
		// echo $dbc->_sql_debug($sql, $arg);
		// echo "\n";
		$chk = $dbc->query($sql, $arg);
		// echo "UPDATE: {$csv_line['@id']} == $chk\n";

	}

	if ($lic_dead) {

		echo "License: $lic_code {$lic_data['name']} is DEAD\n";

		// $license_stat_list1[ $lic_code ]['flag1'] = ($license_stat_list1[ $lic_code ]['flag'] & ~LICENSE_FLAG_CRE_HAVE);
		// $license_stat_list1[ $lic_code ]['stat1'] = 403;
		$sql = 'UPDATE license SET stat = 403 WHERE id = :l0 AND stat != 403';
		$arg = [
			':l0' => $lic_data['id']
		];
		$dbc->query($sql, $arg);

	} else {

		$sql = 'UPDATE license SET stat = 200 WHERE id = :l0 AND stat != 200';
		$arg = [
			':l0' => $lic_data['id']
		];
		$dbc->query($sql, $arg);

	}

	$dbc->update('log_upload', [
		'stat' => $cre_stat,
	], [ 'id' => $req_ulid ]);

	// Archive
	$csv_name = basename($csv_file);
	rename($csv_file, sprintf('%s/var/ccrs-incoming-done/%s', APP_ROOT, $csv_name));

}


/**
 *
 */
function _process_csv_file_b2b_incoming($csv_file, $csv_pipe, $csv_head)
{
	global $dbc;

	$lic_data = [];

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
			_exit_fail_file_move($csv_file, $csv_line, '!! License Not Found');
		}

		// Build ID from Hash
		$b2b_id = md5(sprintf('%s.%s.%s', $csv_line['FromLicenseNumber'], $csv_line['ToLicenseNumber'], $csv_line['TransferDate']));
		$chk = $dbc->fetchRow('SELECT * FROM b2b_incoming WHERE id = :pk', [ ':pk' => $b2b_id ]);
		if (empty($chk)) {
			$dbc->insert('b2b_incoming', [
				'id' => $b2b_id,
				'source_license_id' => $csv_line['FromLicenseNumber'],
				'target_license_id' => $lic_data['id'],
				'stat' => 100,
				'name' => sprintf('Sold By: %s, Ship To: %s', $csv_line['FromLicenseNumber'], $csv_line['ToLicenseNumber'])
			]);
		}

		$err = _process_err_list($csv_line);
		$err_list = $err['data'];

		switch ($err['code']) {
			case 200:
				// Aweomse
				break;
			case 400:
				// Somekind of Errors
				$cre_stat = 400;
				break;
			case 403:
				// Not Authorized on this License
				$cre_stat = 403;
				$lic_dead = true;
				break;
		}

		// Result
		if (count($err_list)) {

			$err_line = implode('; ', $err_list);
			$out_line = implode(',', $csv_line);

			// echo sprintf("%04d:%s\n    !!%s\n", $idx_line, $out_line, $err_line);

		}

		// INSERT or UPDATE
		$sql = "UPDATE b2b_sale_item SET flag = :f1::int, stat = :s1, data = :d1 WHERE id = :pk";
		$arg = [
			':pk' => $csv_line['ExternalId'],
			':f1' => 0, // $cre_flag,
			':s1' => $cre_stat,
			':d1' => json_encode($rec_data)
			// ':cs' => $cre_stat,
		];
		$chk = $dbc->query($sql, $arg);


	}

	$dbc->update('log_upload', [
		'stat' => $cre_stat,
	], [ 'id' => $req_ulid ]);

	// Archive
	$csv_name = basename($csv_file);
	rename($csv_file, sprintf('%s/var/ccrs-incoming-done/%s', APP_ROOT, $csv_name));

}

/**
 * Special Case for Variety
 */
function _csv_file_incoming_variety($csv_file, $csv_pipe, $csv_head)
{
	global $dbc;

	$csv_pkid = 'Strain';
	$tab_name = 'variety';

	$idx_line = 2;

	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;

		switch ($csv_line[4]) {
			case 'CheckSum and number of records don\'t match':
				throw new \Exception('Bad File');
				break;
			// case 'Name is required':
				// break;
			case 'Duplicate Strain/StrainType':
			case 'Duplicate Strain. The Strain must be unique for the LicenseNumber':
				// Ignore
				break;
			default:
				echo sprintf("Error Line: %04d:%s\n", $idx_line, implode(', ', $csv_line));
				// exit(1);
		}

	}

	$dbc->update('log_upload', [
		'stat' => 200, // $cre_stat,
	], [ 'id' => $req_ulid ]);

	// Unlink, not Archive
	unlink($csv_file);

	return(0);

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
			case 'Invalid Area':
				// Try to Patch Realtime?
				// echo '^^ SECTION:';
				// echo implode(',', [
				// 	$License['code']
				// 	, 'Main Section'
				// 	, 'FALSE'
				// 	, sprintf('%s-%s', $License['code'], '018NY6XC00SECT10N000000000')
				// 	, '-system-'
				// 	, date('m/d/Y')
				// 	, '-system-'
				// 	, date('m/d/Y')
				// 	, 'UPDATE'
				// ]);
				// echo "\n";
				// exit(1);
				// break;
			case 'Invalid Strain':
				// var_dump($csv_line);
				// echo '^^ VARIETY:';
				// echo implode(',', [
				// 	$s
				// 	, 'Hybrid'
				// 	, '-system-'
				// 	, date('m/d/Y')
				// ]);
				// echo "\n";
				// exit(1);
				// break;
			case 'Area is required':
			case 'Area name is over 75 characters':
			case 'CreatedDate must be a date':
			case 'ExternalIdentifier is required':
			case 'FromInventoryExternalIdentifier is required':
			case 'InitialQuantity is required':
			case 'InitialQuantity must be numeric':
			case 'Invalid Area':
			case 'Invalid FromInventoryExternalIdentifier':
			case 'Invalid InventoryCategory/InventoryType combination':
			case 'Invalid InventoryExternalIdentifier':
			case 'Invalid LicenseeID':
			case 'Invalid Product':
			case 'Invalid Strain':
			case 'Invalid Strain Type':
			case 'Invalid To InventoryExternalIdentifier':
			case 'Invalid ToInventoryExternalIdentifier':
			case 'InventoryCategory is required':
			case 'InventoryType is required':
			case 'IsMedical must be True or False':
			case 'LicenseNumber is required':
			case 'LicenseNumber must be numeric':
			case 'Name is over 50 characters': // Variety
			case 'Name is over 75 characters':
			case 'Name is required':
			case 'Operation is invalid must be INSERT UPDATE or DELETE':
			case 'Product is required':
			case 'Quantity is required':
			case 'Quantity must be numeric':
			case 'QuantityOnHand must be numeric':
			case 'TotalCost must be numeric':
			case 'UpdatedDate is required for Update or Delete Operations':
			case 'Strain is required':
			case 'Strain Name reported is not linked to the license number. Please ensure the strain being reported belongs to the licensee':
				// Need to Tag this Object as NOT_SYNC
				$err_return_list[] = $err_text;
				break;
			case 'CheckSum and number of records don\'t match':
				// This one is more of a warning but it shows up for every line
				// if the line count NumberRecords field is wrong
				// So, we just ignore it
				break;
			case 'Duplicate External Identifier':
			case 'Duplicate Strain. The Strain must be unique for the LicenseNumber':
				// Cool, this generally means everything is OK
				// BUT!! It could mean a conflict of IDs -- like if the object wasn't for the same license
				// if ('INSERT' == strtoupper($csv_line['Operation'])) {
				// 	$cre_stat = 200;
				// }
				break;
			case 'Integrator is not authoritzed to update licensee':
			case 'Integrator is not authorized to update licensee':
			case 'LicenseNumber is not assigned to Integrator':
			case 'License Number is not assigned to Integrator':
				return [
					'code' => 403,
					'data' => [
						$err_text
					],
				];
				break;
			case 'ExternalIdentifier not found':
				return [
					'code' => 404,
					'data' => [
						$err_text
					],
				];
				break;
			default:
				var_dump($csv_line);
				echo "Unexpected Error: '$err_text'\nLINE: '{$csv_line['ErrorMessage']}'\n";
				exit(1);
		}
	}

	return [
		'code' => (count($err_return_list) > 0 ? 400 : 200),
		'data' => $err_return_list
	];

}
