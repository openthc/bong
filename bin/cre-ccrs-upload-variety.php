#!/usr/bin/php
<?php
/**
 * Find Updated Data
 * Create CCRS Upload CSV
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$dbc = _dbc();

$req_ulid = _ulid();
$csv_data = [];
$csv_data[] = [ "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ];
$csv_file = sprintf('variety_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_head = explode(',', 'Strain,StrainType,CreatedBy,CreatedDate');
$col_size = count($csv_head);
$csv_temp = fopen('php://temp', 'w');



$sql = <<<SQL
SELECT *
FROM variety
WHERE stat = 100 OR updated_at >= :d0
SQL;

$res_variety = $dbc->fetchAll($sql, [
	':d0' => '2022-06-01'
]);
foreach ($res_variety as $variety) {
	$csv_data[] = [
		substr($variety['name'], 0, 50)
		, 'Hybrid'
		, '-system-'
		, date('m/d/Y')
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


// Upload
fseek($csv_temp, 0);


$cfg = array(
	'base_uri' => 'https://bong.openthc.dev/',
	'allow_redirects' => false,
	'cookies' => false,
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
	'body' => $csv_temp // this resource is closed by Guzzle
];
// var_dump($arg);
$res = $api_bong->post('/upload/outgoing', $arg);

$hrc = $res->getStatusCode();
$buf = $res->getBody()->getContents();
$buf = trim($buf);

echo "## BONG $csv_file = $hrc\n";
