<?php
/**
 * Create Upload for Product Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

$dbc = _dbc();

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$license_id = array_shift($argv);
$License = _load_license($dbc, $license_id);

$res_product = $dbc->fetchAll('SELECT * FROM product WHERE license_id = :l0', [ ':l0' => $License['id'] ]);

// Upload
$req_ulid = _ulid();
$csv_name = sprintf('product_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_temp = fopen('php://temp', 'w');

$csv_head = explode(',', 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,UnitWeightGrams,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$col_size = count($csv_head);

$csv_data = [];
$csv_data[] = [ '-canary-', '-canary-', '-canary-', "PRODUCT UPLOAD $req_ulid", '', '0', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];

$res_product = $dbc->fetchAll('SELECT * FROM product WHERE license_id = :l0 AND stat = 100', [ ':l0' => $License['id'] ]);
foreach ($res_product as $product) {

	$product_data = json_decode($product['data'], true);
	$product_source = $product_data['@source'];
	// var_dump($product);
	// var_dump($product_data);
	// exit;

	$dtC = new DateTime($product['created_at']);


	$csv_data[] = [
		$License['code']
		, $product_source['InventoryCategory'] // \OpenTHC\CRE\CCRS::map_product_type0($product['product_type_id']) // Category
		, $product_source['InventoryType'] // \OpenTHC\CRE\CCRS::map_product_type1($product['product_type_id']) // InventoryType
		, substr($product_source['Name'], 0, 75)
		, $product_source['Description']
		, $product_source['UnitWeightGrams'] // sprintf('%0.2f', ('each' == $product['package_type'] ? $product['package_pack_qom'] : 0)) // if BULK use ZERO? // UnitWeightGrams
		, $product['id']
		, '-system-'
		, $dtC->format('m/d/Y')
		, '-system-'
		, date('m/d/Y')
		, 'INSERT'
	];

}
$output_row_count = count($csv_data);
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $output_row_count ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
}
fseek($csv_temp, 0);

_upload_to_queue_only($License, $csv_name, $csv_temp);

unset($csv_temp);
