<?php
/**
 * Create Upload for Variety Data
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Variety;

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

class CSV
{
	/**
	 *
	 */
	function __construct($License)
	{
		$this->_License = $License;
	}

	/**
	 *
	 */
	function create()
	{
		// Check Cache
		$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
			'license' => $this->_License['id'], //  $cli_args['--license'],
			'object' => 'variety',
			// 'force' => $cli_args['--force']
		]);

		// Maybe only do if STAT == 100?
		// Only Create Upload if Data is "FRESH"
		$x = $uphelp->getStatus();
		switch ($x) {
			// case 102: // Processing
			case 202: // Done
				return;
				break;
			default:
				// Needs some Update
		}

		$dbc = _dbc();

		// $cre = new \OpenTHC\CRE\CCRS();
		// $csv = $cre->createCSV('variety');
		// $cre->saveCSV($csv_data?) ?
		$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($api_code, 'variety');

		// Get Data
		$sql = <<<SQL
		SELECT id, name, stat, data
		FROM variety
		WHERE license_id = :l0
		SQL;

		$res_variety = $dbc->fetchAll($sql, [
			':l0' => $this->_License['id'],
		]);
		foreach ($res_variety as $variety) {

			switch ($variety['stat']) {
				case 100:

					$csv->addRow([
						$this->_License['code'] // v1
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
						$this->_License['code'] // v1
						, CCRS::sanatize($variety['name'], 100)
						, 'Hybrid'
						, '-system-'
						, date('m/d/Y')
					]);

					break;

				case 200:

					$csv->addRow([
						$this->_License['code'] // v1
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

		_upload_to_queue_only($this->_License, $csv_name, $csv_temp);

		$uphelp->setStatus(102);

	}
}
