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
	// Check Cache
	$uphelp = new \OpenTHC\BONG\CRE\CCRS\Upload([
		'license' => $cli_args['--license'],
		'object' => 'variety',
		'force' => $cli_args['--force']
	]);
	if (202 == $uphelp->getStatus()) {
		return 0;
	}

	$dbc = _dbc();

	$License = _load_license($dbc, $cli_args['--license'], 'variety');

	// $api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	// $csv = new \OpenTHC\BONG\CRE\CCRS\CSV($api_code, 'variety');

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

				break;

			case 202:

				$dbc->query('UPDATE variety SET data = data #- \'{ "@result" }\' WHERE id = :x0', [
					':x0' => $variety['id'],
				]);

				break;

		}
	}

	// No Data, In Sync
	if (empty($csv_data)) {
		$uphelp->setStatus(202);
		return;
	}

	$req_ulid = _ulid();

	$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$csv_name = sprintf('Strain_%s_%s.csv', $api_code, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,Strain,StrainType,CreatedBy,CreatedDate');
	$col_size = count($csv_head);

	$req_data = [ '-canary-', "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ];
	array_unshift($csv_data, $req_data);
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

}
