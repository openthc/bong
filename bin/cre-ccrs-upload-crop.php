<?php
/**
 * Create Upload for Crop Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_crop($cli_args)
{
	// Check Cache
	$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
		'license' => $cli_args['--license'],
		'object' => 'crop',
		'force' => $cli_args['--force']
	]);

	// Only Create Upload if Needed
	$obj_stat = $uphelp->getStatus();
	switch ($obj_stat) {
	case 102: // Pending
	case 202: // Good
		return;
	}

	// Lets Go!
	$dbc = _dbc();
	$License = _load_license($dbc, $cli_args['--license']);

	$dbc->query('BEGIN');

	$sql = <<<SQL
	SELECT *
	FROM crop
	WHERE license_id = :l0
	  AND stat IN (100, 102, 200, 404)
	ORDER BY stat ASC, updated_at ASC
	SQL;
	$res = $dbc->fetchAll($sql, [
		':l0' => $License['id']
	]);

	$exo = new \OpenTHC\Bong\CRE\CCRS\Crop\Export($License);
	$exo->setData($res);
	$csv_info = $exo->export();

	// No Data, In Sync?
	if (empty($csv_info['row'])) {
		$uphelp->setStatus(202);
		return;
	}

	// 100, 404 => 102
	$dbc->query('UPDATE crop SET stat = 102, data = data #- \'{ "@result" }\' WHERE license_id = :l0 AND stat IN (100, 404)', [
		':l0' => $License['id'],
	]);

	// 200 => 202
	$dbc->query('UPDATE crop SET stat = 202, data = data #- \'{ "@result" }\' WHERE license_id = :l0 AND stat = 200', [
		':l0' => $License['id'],
	]);

	$dbc->query('COMMIT');

	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$req_ulid = _ulid();

	$csv_name = sprintf('Plant_%s_%s.csv', $cre_service_key, $req_ulid);

	_upload_to_queue_only($License, $csv_name, $csv_info['data']);

	$uphelp->setStatus(102);

}
