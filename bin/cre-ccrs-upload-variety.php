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
	$lic = $cli_args['--license'];

	// Check Cache
	$rdb = \OpenTHC\Service\Redis::factory();
	$chk = $rdb->hget(sprintf('/license/%s', $lic), 'variety/stat');
	switch ($chk) {
		case 102:
		case 200:
			return(0);
			break;
		default:
			syslog(LOG_DEBUG, "license:{$lic}; variety-stat={$chk}");
	}


	$dbc = _dbc();

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));

	$License = _load_license($dbc, $cli_args['--license']);

	// Get Data
	$csv_data = [];

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

	// No Data, In Sync
	if (empty($csv_data)) {
		$rdb->hset(sprintf('/license/%s', $License['id']), 'variety/stat', 200);
		$rdb->hset(sprintf('/license/%s', $License['id']), 'variety/stat/time', time());
		$rdb->hset(sprintf('/license/%s', $License['id']), 'variety/sync', 200);
		return;
	}

	$req_ulid = _ulid();

	$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$csv_name = sprintf('Strain_%s_%s.csv', $api_code, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,Strain,StrainType,CreatedBy,CreatedDate');
	$col_size = count($csv_head);

	$csv_data[] = [ '-canary-', "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ];
	$row_size = count($csv_data);

	$csv_temp = fopen('php://temp', 'w');

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

	$rdb->hset(sprintf('/license/%s', $License['id']), 'variety/stat', 102);
	$rdb->hset(sprintf('/license/%s', $License['id']), 'variety/stat/time', time());
	$rdb->hset(sprintf('/license/%s', $License['id']), 'variety/sync', 100);

}
