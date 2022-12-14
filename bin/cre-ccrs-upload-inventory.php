#!/usr/bin/php
<?php
/**
 * Use Curl to upload to the CCRS site
 *
 * SPDX-License-Identifier: MIT
 *
 * Get the cookies from var/
 * Upload the files one at a time, was some transactional lock like issues with bulk
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$dbc = _dbc();

$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $argv[1] ]);
// var_dump($License);

// "1) Area, Strain and Product"
// Variety
// Section
$res_section = $dbc->fetchAll('SELECT * FROM section WHERE license_id = :l0', [ ':l0' => $License['id'] ]);


// Product
$req_ulid = _ulid();
// $csv_file = sprintf('%s/product_%s_%s.csv', $csv_path, $cre_service_key, $req_ulid);
$csv_file = sprintf('product_%s_%s.csv', $cre_service_key, $req_ulid);
// $output_csv = fopen($csv_file, 'w');
$output_csv = fopen('php://temp', 'w');

$csv_head = explode(',', 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,UnitWeightGrams,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$output_col = count($csv_head);

$csv_data = [];
$csv_data[] = [ $cre_canary_code, '-canary-', '-canary-', "PRODUCT UPLOAD $req_ulid", '', '0', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];

$res_product = $dbc->fetchAll('SELECT * FROM product WHERE license_id = :l0 AND stat = 100', [ ':l0' => $License['id'] ]);
foreach ($res_product as $product) {

	$product_data = json_decode($product['data'], true);
	$product_source = $product_data['@source'];

	$dtC = new DateTime($product['created_at']);

	// var_dump($product);
	// var_dump($product_data);
	var_dump($product_source);

	// exit;

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
\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $output_col, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $output_col, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, array_values(array_pad([ 'NumberRecords', $output_row_count ], $output_col, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, array_values($csv_head));
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, $row);
}
// fclose($output_csv);
fseek($output_csv, 0);

$cfg = array(
	'base_uri' => 'https://bong.openthc.com/',
	'allow_redirects' => false,
	'cookies' => false,
	'headers' => array(
		'user-agent' => sprintf('OpenTHC/%s', APP_BUILD),
	),
	'http_errors' => false,
	'verify' => false,
);
$api_bong = new \GuzzleHttp\Client($cfg);

$arg = [
	'headers' => [
		'content-name' => basename($csv_file),
		'content-type' => 'text/csv',
		'openthc-company' => $License['company_id'],
		'openthc-license' => $License['id'],
		'openthc-license-code' => $License['code'],
		'openthc-license-name' => $License['name'],
	],
	'body' => $output_csv // this resource is closed by Guzzle
];
// var_dump($arg);
$res = $api_bong->post('/upload/outgoing', $arg);

$hrc = $res->getStatusCode();
$buf = $res->getBody()->getContents();
$buf = trim($buf);

echo "## BONG $csv_file = $hrc\n";

unset($output_csv);
