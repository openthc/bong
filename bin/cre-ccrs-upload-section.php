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
	// Check Cache
	$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
		'license' => $cli_args['--license'],
		'object' => 'section',
		'force' => $cli_args['--force']
	]);
	if (202 == $uphelp->getStatus()) {
		return 0;
	}

	$dbc = _dbc();
	$License = _load_license($dbc, $cli_args['--license'], 'section');

	$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($api_code, 'section');

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
			case 404:
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
				// $sql = 'UPDATE section SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :s0';
				$sql = 'UPDATE section SET stat = 202 WHERE id = :s0';
				$dbc->query($sql, [
					':s0' => $section['id'],
				]);
				break;
			case 202:
				// $dbc->query('UPDATE section SET data = data #- \'{ "@result" }\' WHERE id = :s0', [
				// 	':s0' => $section['id'],
				// ]);
				break;
			case 400:
			case 403:
				// $cmd = 'INSERT';
				// $dbc->query('UPDATE section SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
				// 	':s0' => $section['id'],
				// ]);
				// break;
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

		$csv->addRow($rec);

	}

	// No Data, In Sync
	if ($csv->isEmpty()) {
		$uphelp->setStatus(202);
		return;
	}

	$csv_name = $csv->getName();
	$csv_temp = $csv->getData('stream');

	_upload_to_queue_only($License, $csv_name, $csv_temp);

	$uphelp->setStatus(102);

}
