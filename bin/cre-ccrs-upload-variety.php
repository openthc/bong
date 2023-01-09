<?php
/**
 * Create Upload for Variety Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

$dbc = _dbc();

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$License = [];

define('CRE_CCRS_VERSION', getenv('CRE_CCRS_VERSION') ?: '2022.343');

switch (CRE_CCRS_VERSION) {
	case '2022.343'	:
		$license_id = array_shift($argv);
		$License = _load_license($dbc, $license_id);
		break;
	case '2021.340':
	default:
		$License = [];
		$License['id'] = \OpenTHC\Config::get('openthc/root/license/id');
		$License['company_id'] = \OpenTHC\Config::get('openthc/root/company/id');
		$License['name'] = '-system-';
		$License['code'] = '-system-';
		break;
}

$req_ulid = _ulid();
$csv_data = [];
$csv_head = [];

switch (CRE_CCRS_VERSION) {
	case '2022.343':
		$csv_data[] = [ '-canary-', "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ];
		$csv_head = explode(',', 'LicenseNumber,Strain,StrainType,CreatedBy,CreatedDate');
		break;
	case '2021.340':
	default:
		$csv_data[] = [ "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ];
		$csv_head = explode(',', 'Strain,StrainType,CreatedBy,CreatedDate');
		break;
}

$csv_name = sprintf('Strain_%s_%s.csv', $cre_service_key, $req_ulid);
$col_size = count($csv_head);
$csv_temp = fopen('php://temp', 'w');


$sql = <<<SQL
SELECT id, name
FROM variety
WHERE license_id = :l0
SQL;

$res_variety = $dbc->fetchAll($sql, [
	':l0' => $License['id'],
]);
foreach ($res_variety as $variety) {
	switch (CRE_CCRS_VERSION) {
		case '2022.343':
			$csv_data[] = [
				$License['code'], // v1
				substr($variety['name'], 0, 50)
				, 'Hybrid'
				, '-system-'
				, date('m/d/Y')
			];
			break;
		case '2021.340':
		default:
			$csv_data[] = [
				substr($variety['name'], 0, 50)
				, 'Hybrid'
				, '-system-'
				, date('m/d/Y')
			];
			break;
	}
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
