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

	// Match Filename
	$message_mime = mailparse_msg_parse_file($message_file); // resource
	$message_part_list = mailparse_msg_get_structure($message_mime); // Array
	// echo "message-part-list: " . implode(' / ', $message_part_list) . "\n";

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

			}
		}
	}

	mailparse_msg_free($message_mime);

	_csv_file_patch($output_file);
	_csv_file_incoming($message_file, $output_file);

	$message_file_done = sprintf('%s/var/ccrs-incoming-mail-done/%s', APP_ROOT, basename($message_file));
	if ( ! rename($message_file, $message_file_done)) {
		throw new \Exception("Cannot archive incoming email");
	}

}

// Cleanup Legacy Data Files
$file_list = glob('/opt/openthc/bong/var/ccrs-incoming/*.csv');
foreach ($file_list as $file) {

	// Patch Text Errors? (see ccrs-incoming in OPS)
	_csv_file_patch($file);
	_csv_file_incoming(null, $file);

}

exit(0);

/**
 * Patch bullshit we find in these files
 */
function _csv_file_patch($csv_file)
{
	// Patch the WHOLE BLOB
	$csv_data = file_get_contents($csv_file);

	// // Fix some bullshit they put in the CSVs (Bug #38)
	$csv_data = str_replace('Insert, Update or Delete', 'INSERT UPDATE or DELETE', $csv_data);
	// // $part_body = str_replace('Operation is invalid must be Insert,  Update or Delete'
	// // 	, 'Operation is invalid must be INSERT UPDATE or DELETE'
	// // 	, $part_body);

	// // This one always goes "comma space space CheckSum"
	// $part_body = str_replace(',  CheckSum and', ': CheckSum and', $part_body);
	// $part_body = preg_replace('/found, CheckSum/i', 'found: CheckSum', $part_body);

	// This one always goes "comma space space CheckSum"
	$csv_data = preg_replace('/(date|licensee), CheckSum and/', '$1: CheckSum and', $csv_data);
	// $data = preg_replace('/found, CheckSum/i', 'found: CheckSum', $data);
	file_put_contents($csv_file, $csv_data);

}

/**
 *
 */
function _csv_file_incoming($source_mail, $csv_file)
{
	global $dbc;

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
			$tab_name = 'b2b_sale_item';
			break;
		case 'LicenseNumber,Name,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			// Section is also used for the PING test
			$tab_name = 'section';
			break;
		case 'CreatedBy,CreatedDate,ErrorMessage,LicenseNumber,ExternalIdentifier,UpdatedBy,UpdatedDate,Operation,Area,Strain,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,InventoryIdentifier':
			$tab_name = 'lot';
			break;
		case 'LicenseNumber,PlantIdentifier,Area,Strain,PlantSource,PlantState,GrowthStage,HarvestCycle,MotherPlantExternalIdentifier,HarvestDate,IsMotherPlant,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage':
			$tab_name = 'crop';
			break;
		case 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation,ErrorMessage,UnitWeightGrams':
			$tab_name = 'product';
			break;
		case 'Strain,StrainType,CreatedBy,CreatedDate,ErrorMessage':
			// @todo this one needs special processing ?
			return _process_csv_file_variety($csv_file, $csv_pipe, $csv_head);
			break;
		default:
			echo "CSV Header Not Handled\n$tab_name";
			exit(0);
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
			$result_data['@result-mail'] = file_get_contents($message_file);
		}
		$update = [
			'result_data' => json_encode($result_data),
			'updated_at' => $csv_time->format(\DateTimeInterface::RFC3339),
		];
		$dbc->update('log_upload', $update, [ 'id' => $req_ulid ]);
	}


	$cre_stat = 200;
	$lic_code = null;
	$lic_dead = false;

	// Should spin the whole file once to verify all the good lines
	// Then spin a second time

	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;
		//echo sprintf("%04d:%s\n", $idx_line, implode(', ', $csv_line));

		if (count($csv_head) != count($csv_line)) {
			var_dump($csv_head);
			var_dump($csv_line);
			echo "Two Lines Don't Match\n";
			exit(1);
		}

		$csv_line = array_combine($csv_head, $csv_line);
		$csv_line['@id'] = $csv_line[$csv_pkid];
		// $csv_line['@license'] = '';


		if (empty($csv_line['LicenseNumber'])) {
			continue;
		}

		// Discover License
		if (empty($lic_code)) {

			$lic_code = $csv_line['LicenseNumber'];
			$lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

		} elseif ($lic_code != $csv_line['LicenseNumber']) {

			$lic_code = $csv_line['LicenseNumber'];
			$lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

		}

		if (empty($lic_data)) {
			_exit_fail_file_move($csv_file, $csv_line, '!! License Not Found');
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

		// Upscale Legacy Data
		if (empty($rec_data['@source']) && empty($rec_data['@result'])) {
			$tmp = [
				'@source' => $rec_data
			];
			$rec_data = $tmp;
		}

		$rec_data['@result'] = $err;

		// if ( ! empty($rec_data['ExternalId']))
		$sql = "UPDATE {$tab_name} SET flag = :f1::int, stat = :s1, data = :d1 WHERE id = :pk";
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
 * Special Case for Variety
 */
function _process_csv_file_variety($csv_file, $csv_pipe, $csv_head)
{
	$csv_pkid = 'Strain';
	$tab_name = 'variety';

	// Canary Line
	$csv_line = fgetcsv($csv_pipe);
	$idx_line++;

	// It's our canary line
	$csv_line_text = implode(',', $csv_line);
	if (preg_match('/VARIETY UPLOAD (01\w{24})/', $csv_line_text, $m)) {
		echo "Canary1: '{$m[1]}'\n";
	}

	while ($csv_line = fgetcsv($csv_pipe)) {

		$idx_line++;

		switch ($csv_line[4]) {
			case 'CheckSum and number of records don\'t match':
				// Error Source File -- How to Find?
				break;
			case 'Duplicate Strain/StrainType':
				// Ignore
				break;
			default:
				echo sprintf("Error Line: %04d:%s\n", $idx_line, implode(', ', $csv_line));
				exit(1);
		}

	}

	unlink($csv_file);

	return(0);

}

/**
 *
 */
function _process_err_list($csv_line)
{
	$err_return_list = [];

	$err_text_list = explode(':', $csv_line['ErrorMessage']);

	// we have to foreach err_text
	foreach ($err_text_list as $err_text) {
		$err_text = trim($err_text);
		switch ($err_text) {
			case 'Area name is over 75 characters':
			case 'CreatedDate must be a date':
			case 'ExternalIdentifier not found':
			case 'ExternalIdentifier is required':
			case 'InitialQuantity is required':
			case 'InitialQuantity must be numeric':
			case 'Invalid Area':
			case 'Invalid InventoryCategory/InventoryType combination':
			case 'Invalid LicenseeID':
			case 'Invalid Product':
			case 'Invalid Strain':
			case 'Invalid Strain Type':
			case 'IsMedical must be True or False':
			case 'LicenseNumber is required':
			case 'LicenseNumber must be numeric':
			case 'Name is over 50 characters':
			case 'Name is over 75 characters':
			case 'Name is required':
			case 'Operation is invalid must be INSERT UPDATE or DELETE':
			case 'QuantityOnHand must be numeric':
			case 'TotalCost must be numeric':
			case 'UpdatedDate is required for Update or Delete Operations':
				// Need to Tag this Object as NOT_SYNC
				$err_return_list[] = $err_text;
				break;
			case 'CheckSum and number of records don\'t match':
				// This one is more of a warning but it shows up for every line
				// if the line count NumberRecords field is wrong
				// So, we just ignore it
				break;
			case 'Duplicate External Identifier':
				// Cool, this generally means everything is OK
				// BUT!! It could mean a conflict of IDs -- like if the object wasn't for the same license
				// if ('INSERT' == strtoupper($csv_line['Operation'])) {
				// 	$cre_stat = 200;
				// }
				break;
			case 'Integrator is not authoritzed to update licensee':
			case 'Integrator is not authorized to update licensee':
			case 'License Number is not assigned to Integrator':
				// Del Flag
				// $license_stat_list1[ $lic_code ]['flag1'] = $license_stat_list1[ $lic_code ]['flag'] & ~LICENSE_FLAG_CRE_HAVE;
				// $license_stat_list1[ $lic_code ]['stat1'] = 403;
				$lic_dead = true;
				return [
					'code' => 403,
					'data' => [
						$err_text
					],
				];
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
