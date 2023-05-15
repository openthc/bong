<?php
/**
 * Create Upload for B2B Incoming Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_b2b_outgoing($cli_args)
{
	// Check Cache
	$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
		'license' => $cli_args['--license'],
		'object' => 'b2b/outgoing',
		'force' => $cli_args['--force']
	]);
	if (202 == $uphelp->getStatus()) {
		return 0;
	}

	$dbc = _dbc();

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

	$License = _load_license($dbc, $cli_args['--license']);

	// CSV Data
	$req_ulid = _ulid();
	$csv_data = [];
	$csv_data[] = [ '-canary-', '-canary-', "B2B_OUTGOING UPLOAD $req_ulid", '-canary-', '0', '', '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
	$csv_head = explode(',', 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,SalesTax,OtherTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$csv_name = sprintf('Sale_%s_%s.csv', $cre_service_key, $req_ulid);
	$col_size = count($csv_head);


	$sql = <<<SQL
	SELECT b2b_outgoing.id AS b2b_outgoing_id
		, b2b_outgoing.created_at
		, b2b_outgoing.updated_at
		, b2b_outgoing.data AS b2b_outgoing_data
		, b2b_outgoing_item.id AS b2b_outgoing_item_id
		, b2b_outgoing_item.data AS b2b_outgoing_item_data
		, b2b_outgoing_item.stat
	FROM b2b_outgoing
	JOIN b2b_outgoing_item ON b2b_outgoing.id = b2b_outgoing_item.b2b_outgoing_id
	JOIN license AS source_license ON b2b_outgoing.source_license_id = source_license.id
	-- JOIN license AS target_license ON b2b_outgoing.target_license_id = target_license.id
	WHERE b2b_outgoing.source_license_id = :l0
	SQL;

	$arg = [ ':l0' => $License['id'] ];

	$res_b2b_outgoing_item = $dbc->fetchAll($sql, $arg);
	foreach ($res_b2b_outgoing_item as $b2b_outgoing_item) {

		$dtC = new DateTime($b2b_outgoing_item['created_at'], $tz0);
		$dtU = new DateTime($b2b_outgoing_item['updated_at'], $tz0);

		$src_b2b = json_decode($b2b_outgoing_item['b2b_outgoing_data'], true);
		$src_b2b = $src_b2b['@source'];

		$src_b2b_item = json_decode($b2b_outgoing_item['b2b_outgoing_item_data'], true);
		$src_b2b_item = $src_b2b_item['@source'];

		$cmd = '';
		switch ($b2b_outgoing_item['stat']) {
			case 100:
				$cmd = 'INSERT';
				$dbc->query('UPDATE b2b_outgoing_item SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $b2b_outgoing_item['b2b_outgoing_item_id'],
				]);
				break;
			case 102:
				$cmd = 'INSERT';
				break;
			case 200:
				$cmd = 'UPDATE';
				break;
		}

		if (empty($cmd)) {
			continue;
		}

		$rec = [
			$License['code'] // LicenseNumber
			, $src_b2b['target']['code'] // SoldToLicenseNumber
			, $src_b2b_item['lot']['id'] // InventoryExternalIdentifier
			, '' // PlantExternalIdentifier
			, 'Wholesale' // SaleType
			, $dtC->format('m/d/Y') // SaleDate
			, $src_b2b_item['unit_count'] // Quantity
			, $src_b2b_item['unit_price'] // UnitPrice
			, '0' // Discount
			, '0' // SalesTax
			, '0' // OtherTax
			, $src_b2b['id'] // SaleExternalIdentifier
			, $src_b2b_item['id'] // SaleDetailExternalIdentifier
			, '-system-' // CreatedBy
			, $dtC->format('m/d/Y') // CreatedDate
			, '-system-' // UpdatedBy
			, $dtU->format('m/d/Y') // UpdatedDate
			, $cmd // OPERATION
		];

		// var_dump($b2b_outgoing_item);
		// unset($src_b2b['item_list']);
		// var_dump($src_b2b);
		// var_dump($src_b2b_item);
		// var_dump($src);
		// var_dump($rec); exit;

		$csv_data[] = $rec;

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

	$uphelp->setStatus(102);

}
