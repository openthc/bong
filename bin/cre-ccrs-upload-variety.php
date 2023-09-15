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
	$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
		'license' => $cli_args['--license'],
		'object' => 'variety',
		'force' => $cli_args['--force']
	]);
	if (202 == $uphelp->getStatus()) {
		return 0;
	}

	$dbc = _dbc();
	$License = _load_license($dbc, $cli_args['--license'], 'variety');

	$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($api_code, 'variety');

	// Get Data
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

				$csv->addRow([
					$License['code'] // v1
					, CCRS::sanatize($variety['name'], 100)
					, 'Hybrid'
					, '-system-'
					, date('m/d/Y')
				]);

				$dbc->query('UPDATE variety SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $variety['id'],
				]);

				break;

			case 102: // Upload a Second Time, No Flag

				$csv->addRow([
					$License['code'] // v1
					, CCRS::sanatize($variety['name'], 100)
					, 'Hybrid'
					, '-system-'
					, date('m/d/Y')
				]);

				break;

			case 200:

				$csv->addRow([
					$License['code'] // v1
					, CCRS::sanatize($variety['name'], 100)
					, 'Hybrid'
					, '-system-'
					, date('m/d/Y')
				]);

				$dbc->query('UPDATE variety SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :x0', [
					':x0' => $variety['id'],
				]);

				break;

		}
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
