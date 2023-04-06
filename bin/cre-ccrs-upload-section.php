<?php
/**
 * Create Upload for Section Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_section($cli_args)
{
	$lic = $cli_args['--license'];

	// Check Cache
	$rdb = \OpenTHC\Service\Redis::factory();
	$chk = $rdb->hget(sprintf('/license/%s', $lic), 'section/stat');
	switch ($chk) {
		case 102:
		case 200:
			return(0);
			break;
		default:
			syslog(LOG_DEBUG, "license:{$lic}; section-stat={$chk}");
	}

	$dbc = _dbc();
	$License = _load_license($dbc, $lic);

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));

	// Get Data
	$csv_data = [];

	$sql = <<<SQL
	SELECT section.*, license.code AS license_code
	FROM section
	JOIN license ON section.license_id = license.id
	AND license.id = :l0
	SQL;
	$arg = [ ':l0' => $License['id'] ];

	if ( ! empty($cli_args['--object-id'])) {
		$sql.= ' AND section.id = :pk';
		$arg[':pk'] = $cli_args['--object-id'];
	}

	$res_section = $dbc->fetchAll($sql, $arg);
	foreach ($res_section as $section) {

		$dtC = new \DateTime($section['created_at'], $tz0);
		$dtU = new \DateTime($section['updated_at'], $tz0);

		$cmd = '';
		switch ($section['stat']) {
			case 100:
				$cmd = 'INSERT'; // Moves to 404 via CCRS Response
				$dbc->query('UPDATE section SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $section['id'],
				]);
				break;
			case 102:
				$cmd = 'INSERT';
				break;
			case 200:
				// Move to 202 -- will get error from CCRS if NOT Good
				$cmd = 'UPDATE';
				$dbc->query('UPDATE section SET stat = 202 WHERE id = :s0', [
					':s0' => $section['id'],
				]);
				break;
			case 202:
				// Ignore
				break;
			case 404:
				$cmd = 'INSERT';
				$dbc->query('UPDATE section SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $section['id'],
				]);
				break;
			case 410:
				// $cmd = 'DELETE'; // Move to 666 ?
				// continue 2; // foreach
				break;
			default:
				throw new \Exception("Invalid Section Status '{$section['stat']}'");
		}

		if (empty($cmd)) {
			continue;
		}

		// Record
		$rec = [
			$section['license_code']
			, CCRS::sanatize($section['name'], 50)
			, 'FALSE'
			, $section['id']
			, '-system-'
			, $dtC->format('m/d/Y')
			, '-system-'
			, $dtU->format('m/d/Y')
			, $cmd
		];

		$csv_data[] = $rec;

	}

	// No Data, In Sync
	if (empty($csv_data)) {
		$rdb->hset(sprintf('/license/%s', $License['id']), 'section/stat', 200);
		$rdb->hset(sprintf('/license/%s', $License['id']), 'section/stat/time', time());
		$rdb->hset(sprintf('/license/%s', $License['id']), 'section/sync', 200);
		return;
	}

	$req_ulid = _ulid();

	$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$csv_name = sprintf('Area_%s_%s.csv', $api_code, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);

	$csv_data[] = [ '-canary-', "SECTION UPLOAD $req_ulid", 'FALSE', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
	$row_size = count($csv_data);

	$csv_temp = fopen('php://temp', 'w');

	// Output
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

	$rdb->hset(sprintf('/license/%s', $License['id']), 'section/stat', 102);
	$rdb->hset(sprintf('/license/%s', $License['id']), 'section/stat/time', time());
	$rdb->hset(sprintf('/license/%s', $License['id']), 'section/sync', 100);

}
