<?php
/**
 * Create Upload for Variety Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_variety($cli_args)
{
	$R = \OpenTHC\Service\Redis::factory();
	$chk = $R->get(sprintf('/license/%s/variety', $License['id']));
	syslog(LOG_DEBUG, "license:{$License['id']}; variety-stat={$chk}");


	$dbc = _dbc();

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

	$License = [];

	$License = _load_license($dbc, $cli_args['--license']);

	$req_ulid = _ulid();
	$csv_data = [];
	$csv_head = [];

	$csv_data[] = [ '-canary-', "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ];
	$csv_head = explode(',', 'LicenseNumber,Strain,StrainType,CreatedBy,CreatedDate');

	$csv_name = sprintf('Strain_%s_%s.csv', $cre_service_key, $req_ulid);
	$col_size = count($csv_head);
	$csv_temp = fopen('php://temp', 'w');


	$sql = <<<SQL
	SELECT id, name, stat, data
	FROM variety
	WHERE license_id = :l0
	SQL;

	$res_variety = $dbc->fetchAll($sql, [
		':l0' => $License['id'],
	]);
	foreach ($res_variety as $variety) {

		if (preg_match('/Duplicate Strain/', $variety['data'])) {
			$variety['stat'] = 100;
		}

		switch ($variety['stat']) {
			case 100:

				$csv_data[] = [
					$License['code'] // v1
					, CCRS::sanatize($variety['name'], 100)
					, 'Hybrid'
					, '-system-'
					, date('m/d/Y')
				];

				$dbc->query('UPDATE variety SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $variety['id'],
				]);

				break;

			case 102: // Upload a Second Time, No Flag

				$csv_data[] = [
					$License['code'] // v1
					, CCRS::sanatize($variety['name'], 100)
					, 'Hybrid'
					, '-system-'
					, date('m/d/Y')
				];

		}
	}

	$row_size = count($csv_data);
	if ($row_size <= 1) {
		echo "No Data to Upload\n";
		return(0);
	}

	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}

	// Upload
	fseek($csv_temp, 0);

	_upload_to_queue_only($License, $csv_name, $csv_temp);

	unset($csv_temp);

	$R->set(sprintf('/license/%s/variety', $License['id']), 200);

}
