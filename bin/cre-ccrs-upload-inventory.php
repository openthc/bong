<?php
/**
 * Create Upload for Inventory Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_inventory($cli_args)
{

	$dbc = _dbc();

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

	$License = _load_license($dbc, $cli_args['--license']);

	// CSV Data
	$req_ulid = _ulid();

	$csv_data = [];
	$csv_data[] = [ '-canary-', '-canary-', '-canary-', '-canary-', '0', '0', '0', 'FALSE', "INVENTORY UPLOAD $req_ulid", '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
	$csv_head = explode(',', 'LicenseNumber,Strain,Area,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$csv_name = sprintf('Inventory_%s_%s.csv', $cre_service_key, $req_ulid);
	$col_size = count($csv_head);

	$sql = <<<SQL
	SELECT *
	FROM lot
	WHERE license_id = :l0
	  AND stat IN (100, 102, 200)
	ORDER BY stat ASC, updated_at ASC
	LIMIT 2500
	SQL;

	$res_inventory = $dbc->fetchAll($sql, [ ':l0' => $License['id'] ]);
	foreach ($res_inventory as $inv) {

		$inv_data = json_decode($inv['data'], true);
		$inv_source = $inv_data['@source'];

		if (empty($inv_data['@version'])) {
			// var_dump($inv); exit;
			$inv_source = [
				'qty' => $inv_data['@source']['QuantityOnHand'],
				'qty_initial' => $inv_data['@source']['InitialQuantity'],
				'variety' => [
					'name' => $inv_data['@source']['Strain']
				],
				'section' => [
					'name' => $inv_data['@source']['Area'],
				],
				'product' => [
					'name' => $inv_data['@source']['Product'],
				]
			];
		}

		$dtC = new DateTime($inv['created_at']);
		$dtC->setTimezone($tz0);

		$dtU = new DateTime($inv['updated_at']);
		$dtU->setTimezone($tz0);

		$cmd = '';
		switch ($inv['stat']) {
			case 100:
			case 404:
				$cmd = 'INSERT';
				$dbc->query('UPDATE lot SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $inv['id'],
				]);
				break;
			case 102:
				$cmd = 'INSERT';
				break;
			case 200:
				// $cmd = 'UPDATE';
				// $dbc->query('UPDATE lot SET stat = 202 WHERE id = :s0', [
				// 	':s0' => $inv['id'],
				// ]);
				break;
			case 202:
				// Fully Uploaded
				break;
			case 400:
				// $cmd = 'UPDATE';
				break;
			// case 404:
			// 	$cmd = 'INSERT';
			// 	break;
			case 410:
			case 666:
				// $cmd = 'DELETE';
				break;
			default:
				throw new \Exception("Invalid Inventory Stat '{$inv['stat']}'");
		}

		if (empty($cmd)) {
			// echo "SKIP: {$inv['id']}\n";
			continue;
		}

		// Insert
		$row = [
			$License['code']
			, \OpenTHC\CRE\CCRS::sanatize($inv_source['variety']['name'], 100)
			, \OpenTHC\CRE\CCRS::sanatize($inv_source['section']['name'], 50)
			, \OpenTHC\CRE\CCRS::sanatize($inv_source['product']['name'], 75)
			, sprintf('%0.2f', $inv_source['qty_initial'])
			, sprintf('%0.2f', $inv_source['qty'])
			, 0
			, 'FALSE'
			, $inv['id']
			, '-system-'
			, $dtC->format('m/d/Y')
			, '-system-'
			, $dtU->format('m/d/Y')
			, $cmd
		];

		$csv_data[] = $row;

	}

	$row_size = count($csv_data);
	if ($row_size <= 1) {
		echo "No Data to Upload\n";
		return(0);
	}

	$csv_temp = fopen('php://temp', 'w');
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}
	fseek($csv_temp, 0);

	// Upload
	_upload_to_queue_only($License, $csv_name, $csv_temp);

	unset($csv_temp);

}
