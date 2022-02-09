#!/usr/bin/php
<?php
/**
 * Process CCRS Incoming Files
 *
 * SPDX-License-Identifier: GPL-3.0-only
 */

use \Edoceo\Radix\DB\SQL;

require_once(__DIR__ . '/../boot.php');

$dbc = _dbc();

// Match Filename
// $name = preg_match('/^(01\w{24})$/', $_GET['f'], $m) ? $m[1] : null;
// $file = sprintf('%s/var/ccrs-incoming/%s.csv', $name);
// if ( ! is_file($file)) {
	// __exit_text('Invalid File', 400);
// }

$file_list = glob('/opt/openthc/bong/var/ccrs-incoming/*.csv');
foreach ($file_list as $file) {

	// Patch Text Errors? (see ccrs-incoming in OPS)
	_process_csv_file($file);

}

exit(0);

function _process_csv_file($file)
{
	global $dbc;

	echo "CSV File: $file\n";

	// Patch the WHOLE BLOB
	$data = file_get_contents($file);

	// // Fix some bullshit they put in the CSVs (Bug #38)
	$data = str_replace('Insert, Update or Delete', 'INSERT UPDATE or DELETE', $data);
	// // $part_body = str_replace('Operation is invalid must be Insert,  Update or Delete'
	// // 	, 'Operation is invalid must be INSERT UPDATE or DELETE'
	// // 	, $part_body);

	// // This one always goes "comma space space CheckSum"
	// $part_body = str_replace(',  CheckSum and', ': CheckSum and', $part_body);
	// $part_body = preg_replace('/found, CheckSum/i', 'found: CheckSum', $part_body);

	// This one always goes "comma space space CheckSum"
	$data = preg_replace('/(date|licensee), CheckSum and/', '$1: CheckSum and', $data);
	// $data = preg_replace('/found, CheckSum/i', 'found: CheckSum', $data);
	file_put_contents($file, $data);

	// Need to actually keep file name to understand the ?
	$csv_file = fopen($file, 'r');
	$idx_line = 1;
	$csv_head = fgetcsv($csv_file);
	$csv_head = array_values($csv_head);
	$row_size = count($csv_head);

	$csv_pkid = 'ExternalIdentifier';
	// $tab_name = preg_match('/^([a-z]+)_/', $csv_name, $m) ? $m[1] : null;
	// switch ($tab_name) {
	// 	case 'inventory':
	// 		$tab_name = 'lot';
	// 		break;
	// 	case 'plant':
	// 		$tab_name = 'crop';
	// 		break;
	// 	case 'product':
	// 		// OK
	// 		break;
	// 	default:
	// 		return $RES->withJSON([
	// 			'data' => null,
	// 			'meta' => [ 'detail' => 'Invalid File Type' ]
	// 		], 400);
	// }


	// Assemble Header Line to determine Type
	$tab_name = implode(',', $csv_head);
	switch ($tab_name) {
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
			return _process_csv_file_variety($csv_file, $csv_head);
			break;
		default:
			echo "CSV Header Not Handled\n$tab_name";
			exit(0);
			break;
	}

	// echo "CSV Type: $tab_name\n";

	// Canary Line
	$csv_line = fgetcsv($csv_file);
	$idx_line++;

	// It's our canary line
	$csv_line_text = implode(',', $csv_line);
	if (preg_match('/(\w+ UPLOAD.+01\w{24}).+\-canary\-/', $csv_line_text, $m)) {
		echo "Canary1: '{$m[1]}'\n";
	} elseif (preg_match('/(\w+ UPLOAD.+01\w{24})/', $csv_line_text, $m)) {
		echo "Canary2: '{$m[1]}'\n";
	} elseif (preg_match('/(PING (INSERT|UPDATE).+01\w{24})/', $csv_line_text, $m)) {
		echo "Canary3: '{$m[1]}'\n";
	} else {
		echo "Canary??:  $csv_line_text\n";
	}

	$lic_code = null;
	$lic_dead = false;

	// Should spin the whole file once to verify all the good lines
	// Then spin a second time

	while ($csv_line = fgetcsv($csv_file)) {

		$idx_line++;
		//echo sprintf("%04d:%s\n", $idx_line, implode(', ', $csv_line));

		if (count($csv_head) != count($csv_line)) {
			var_dump($csv_head);
			var_dump($csv_line);
			echo "Two Lines Don't Match\n";
			exit(1);
		}

		$csv_line = array_combine($csv_head, $csv_line);
		$rec_guid = $csv_line[$csv_pkid];
		// echo "Record: $rec_guid\n";

		if (empty($csv_line['LicenseNumber'])) {
			continue;
		}

		// Discover License
		if (empty($lic_code)) {

			$lic_code = $csv_line['LicenseNumber'];
			$lic_data = $dbc->fetchRow('SELECT * FROM license WHERE code = :l0', [ ':l0' => $lic_code ]);

			// $sql_file = sprintf('%s/var/%s.sqlite', APP_ROOT, $lic_data['hash']);
			// if ( ! is_file($sql_file)) {
			// 	echo "Missing License Database for $lic_code\n";
			// 	exit(1);
			// }
			// $dbc_user = new SQL(sprintf('sqlite:%s', $sql_file));

		} elseif ($lic_code != $csv_line['LicenseNumber']) {

			$lic_code = $csv_line['LicenseNumber'];

			// echo "License Switched in a File, I don't like that\n";
			// echo "  '$lic_code' != '{$csv_line['LicenseNumber']}'\n";
			// var_dump($csv_line);
			// exit(1);
		}

		$cre_stat = 200;

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

			echo sprintf("%04d:%s\n    !!%s\n", $idx_line, $out_line, $err_line);

		}

		// Inflate the Old Data
		$rec_data = $dbc->fetchOne("SELECT data FROM {$tab_name} WHERE id = :pk", [
			':pk' => $rec_guid
		]);
		if (empty($rec_data)) {
			// Fake It
			$rec_data = [
				'soruce' => $csv_line,
			];
		} else {
			$rec_data = json_decode($rec_data, true);
		}

		// Upscale Legacy Data
		if (empty($rec_data['source']) && empty($rec_data['result'])) {
			$tmp = [
				'source' => $rec_data
			];
			$rec_data = $tmp;
		}

		$rec_data['result'] = $err;

		// if ( ! empty($rec_data['ExternalId']))
		$sql = "UPDATE {$tab_name} SET flag = :f1::int, stat = :s1, data = :d1 WHERE id = :pk";
		$arg = [
			':pk' => $rec_guid,
			':f1' => 0, // $cre_flag,
			':s1' => $cre_stat,
			':d1' => json_encode($rec_data)
			// ':cs' => $cre_stat,
		];
		$chk = $dbc->query($sql, $arg);
		echo "UPDATE: $rec_guid == $chk\n";

	}

	if ($lic_dead) {
		echo "License: $lic_code is DEAD\n";
		// $license_stat_list1[ $lic_code ]['flag1'] = ($license_stat_list1[ $lic_code ]['flag'] & ~LICENSE_FLAG_CRE_HAVE);
		// $license_stat_list1[ $lic_code ]['stat1'] = 403;
	}


	unlink($file);

	// Necessary or only on ping?
	// if ('Section' == $tab_name) {
	// 	foreach ($license_stat_list1 as $lic_code => $lic_stat) {

	// 		$flag0 = $lic_stat['flag'];
	// 		$stat0 = $lic_stat['stat'];

	// 		$flag1 = $lic_stat['flag1'];
	// 		$stat1 = $lic_stat['stat1'];

	// 		if (($stat0 != $stat1) || ($flag0 != $flag1)) {
	// 			echo "Update License: $lic_code $stat0/$flag0 => $stat1/$flag1\n";
	// 			$update = [];
	// 			$update['stat'] = $stat1;
	// 			$update['flag'] = $flag1;
	// 			$dbc_auth->update('license', $update, [ 'id' => $lic_stat['id'] ]);
	// 		}

	// 	}
	// }

}

/**
 *
 */
function _process_csv_file_variety($csv_file, $csv_head)
{
	$csv_pkid = 'Strain';
	$tab_name = 'variety';
	unlink($file);
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
				// break 2;
			// case 'Duplicate Strain/StrainType':
			// 	// Ignore
			// 	break;
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
