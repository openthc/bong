<?php
/**
 * Create Upload for Inventory Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

$dbc = _dbc();

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$license_id = array_shift($argv);
$License = _load_license($dbc, $license_id);

$res_inventory = $dbc->fetchAll('SELECT * FROM lot WHERE license_id = :l0', [ ':l0' => $License['id'] ]);

// Build CSV
$req_ulid = _ulid();
$csv_name = sprintf('inventory_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_temp = fopen('php://temp', 'w');

$csv_head = explode(',', 'LicenseNumber,Strain,Area,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$col_size = count($csv_head);

$csv_data = [];
$csv_data[] = [ '-canary-', '-canary-', '-canary-', '-canary-', '0', '0', '0', 'FALSE', "INVENTORY UPLOAD $req_ulid", '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];

foreach ($res_inventory as $inv) {

	// $inv_data = json_decode($inv['data'], true);
	// $inv_source = $inv_data['@source'];

	$dtC = new DateTime($inv['created_at']);

	// var_dump($inv);
	// var_dump($inv_data);
	// var_dump($inv_source);

	$dtC = new DateTime($inv['created_at'], $tz0);

	// Insert
	$rec = [
		$License['code']
		, substr($inv['variety_name'], 0, 50)
		, $inv['section_name']
		, substr($inv['product_name'], 0, 75)
		, sprintf('%0.2f', $inv['qty_initial'])
		, sprintf('%0.2f', $inv['qty'])
		, 0
		, 'FALSE'
		, $inv['id']
		, '-system-'
		, $dtC->format('m/d/Y')
		, '-system-'
		, date('m/d/Y')
		, 'UPDATE'
	];

	$csv_data[] = $rec;

}
$output_row_count = count($csv_data);
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $output_row_count ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
}
// fclose($csv_temp);
fseek($csv_temp, 0);

_upload_to_queue_only($License, $csv_name, $csv_temp);

unset($csv_temp);
