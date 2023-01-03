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

// CSV Data
$req_ulid = _ulid();
$csv_data = [];
$csv_data[] = [ '-canary-', '-canary-', '-canary-', "PRODUCT UPLOAD $req_ulid", '', '0', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
$csv_head = explode(',', 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,UnitWeightGrams,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$csv_name = sprintf('Product_%s_%s.csv', $cre_service_key, $req_ulid);
$col_size = count($csv_head);


$res_product = $dbc->fetchAll('SELECT * FROM product WHERE license_id = :l0', [ ':l0' => $License['id'] ]);
// $res_product = $dbc->fetchAll('SELECT * FROM product WHERE license_id = :l0 AND stat = 100', [ ':l0' => $License['id'] ]);
foreach ($res_product as $product) {

	$product_data = json_decode($product['data'], true);
	$product_source = $product_data['@source'];
	// $product_source['note'] = 'NOTE';
	// var_dump($product_data);

	$dtC = new DateTime($product['created_at']);
	$dtU = new DateTime($product['updated_at']);

	$row = [
		$License['code']
		, \OpenTHC\CRE\CCRS::map_product_type0($product_source['type'])
		, \OpenTHC\CRE\CCRS::map_product_type1($product_source['type'])
		, substr($product['name'], 0, 75)
		, $product_source['note']
		, 0 // 5; sprintf('%0.2f', $product_source['package']['unit']['weight']) // sprintf('%0.2f', ('each' == $product['package_type'] ? $product['package_pack_qom'] : 0)) // if BULK use ZERO? // UnitWeightGrams
		, $product['id']
		, '-system-'
		, $dtC->format('m/d/Y')
		, '-system-'
		, $dtU->format('m/d/Y')
		, 'INSERT'
	];

	switch ($product_source['package']['type']) {
		case 'bulk':
			$row[5] = -1;
			break;
		case 'each':
			$row[5] = sprintf('%0.2f', $product_source['package']['unit']['weight']);
			break;
	}

	$csv_data[] = $row;

}

$row_size = count($csv_data);

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
