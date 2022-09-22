#!/usr/bin/php
<?php
/**
 * Checks All CCRS Licenses for Access
 *
 * SPDX-License-Identifier: MIT
 */

require_once(dirname(dirname(__FILE__)) . '/boot.php');

$dbc = _dbc();

// Generate dummy file to CCRS
$cre_upload_ulid = _ulid();
$cre_software_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
$csv_date = date('YmdHis');
$csv_file_list = [];

$csv_data = [];

$res_license = $dbc->fetchAll('SELECT * FROM license');
foreach ($res_license as $l) {
	$csv_data[] = [
		$l['code']
		, 'Main Section'
		, 'FALSE'
		, sprintf('%s-%s', $l['code'], '018NY6XC00SECT10N000000000')
		, '-system-'
		, date('m/d/Y')
		, ''
		, ''
		, 'INSERT'
	];

}

// INSERT FILE
$csv_file = sprintf('%s/area_%s_%s.csv', $out_path, $cre_software_key, $cre_upload_ulid);
$csv_head = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$output_row_count = count($csv_data);
// $output_csv = fopen($csv_file, 'w');
$output_csv = fopen('php://stdout', 'w');
$output_col = count($csv_head);
fputcsv($output_csv, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $output_col, '')));
fputcsv($output_csv, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $output_col, '')));
fputcsv($output_csv, array_values(array_pad([ 'NumberRecords', $output_row_count  + 1], $output_col, '')));
fputcsv($output_csv, array_values($csv_head));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, [ '226279', 'PING SECTION', 'FALSE', "UPLOAD $cre_upload_ulid", '-canary-', date('m/d/Y'), '', '', 'INSERT' ]);
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, $row);
}
fclose($output_csv);

// Now the Update File
// Remove the Final Row, Make a new One
// $cre_upload_ulid = _ulid();
// $csv_file = sprintf('%s/area_%s_%s.csv', $out_path, $cre_software_key, $cre_upload_ulid);
// $output_row_count = count($csv_data);
// $output_csv = fopen($csv_file, 'w');
// $output_col = count($csv_head);
// fputcsv($output_csv, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $output_col, '')));
// fputcsv($output_csv, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $output_col, '')));
// fputcsv($output_csv, array_values(array_pad([ 'NumberRecords', $output_row_count + 1 ], $output_col, '')));
// fputcsv($output_csv, array_values($csv_head));
// fputcsv($output_csv, [ '226279', "PING UPDATE $cre_upload_ulid" ]);
// foreach ($csv_data as $row) {
// 	$row[1] = trim(preg_replace('/^01\w+\-01\w+/', '', $row[1]));
// 	$row[8] = 'UPDATE';
// 	\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, $row);
// }
// fclose($output_csv);
// $csv_file_list[] = $csv_file;
