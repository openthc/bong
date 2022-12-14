<?php
/**
 * Create Upload for Variety Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$dbc = _dbc();

// CCRS v2022-343
// $License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $argv[1] ]);

// CCRS v2021-340
$License = [];
$License['id'] = \OpenTHC\Config::get('openthc/root/license/id');
$License['company_id'] = \OpenTHC\Config::get('openthc/root/company/id');
$License['name'] = '-system-';
$License['code'] = '-system-';


$req_ulid = _ulid();
$csv_data = [];
$csv_data[] = [ "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ]; // v2021-340
// $csv_data[] = [ '-canary-', "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ]; // v2022-343
$csv_name = sprintf('strain_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_head = explode(',', 'Strain,StrainType,CreatedBy,CreatedDate'); // v0
// $csv_head = explode(',', 'LicenseNumber,Strain,StrainType,CreatedBy,CreatedDate'); // v1
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
		// $License['code'], // v1
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

_upload_to_queue_only($License, $csv_name, $csv_temp);

unset($csv_temp);
