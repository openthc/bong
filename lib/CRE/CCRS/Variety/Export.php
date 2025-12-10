<?php
/**
 * Create Upload for Variety Data
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Variety;

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

class Export
{
	/**
	 *
	 */
	function __construct($License)
	{
		$this->_License = $License;
		$this->_tz0 = new \DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
	}

	/**
	 *
	 */
	function create($force=false)
	{
		// Check Cache
		$status = new \OpenTHC\Bong\CRE\CCRS\Status($this->_License['id'], 'variety');
		$chk = $status->getStat();
		switch ($chk) {
			case 202:
				return;
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
		AND stat IN (100, 102, 200, 404)
		ORDER BY id
		SQL;

		$res_variety = $dbc->fetchAll($sql, [
			':l0' => $this->_License['id'],
		]);
		foreach ($res_variety as $variety) {

			$dtC = new \DateTime($variety['created_at'], $this->_tz0);
			$dtC->setTimezone($this->_tz0);

			switch ($variety['stat']) {
				case 100:

					$csv->addRow([
						$this->_License['code'] // v1
						, CCRS::sanatize($variety['name'], 100)
						, 'Hybrid'
						, '-system-'
						, $dtC->format('m/d/Y')
					]);

					$dbc->query('UPDATE variety SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $variety['id'],
					]);

					break;

				case 102:

					$dbc->query('UPDATE variety SET stat = 200, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $variety['id'],
					]);

					break;

				case 200:

					$csv->addRow([
						$this->_License['code'] // v1
						, CCRS::sanatize($variety['name'], 100)
						, 'Hybrid'
						, '-system-'
						, $dtC->format('m/d/Y')
					]);

					$dbc->query('UPDATE variety SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :x0', [
						':x0' => $variety['id'],
					]);

					break;

			}
		}

		// No Data, In Sync
		if ($csv->isEmpty()) {
			$status->setPush(202);
			return;
		}

		$csv_name = $csv->getName();
		$csv_temp = $csv->getData('stream');

		\OpenTHC\Bong\CRE\CCRS\Upload::enqueue($this->_License, $csv_name, $csv_temp);

		$status->setPush(102);

	}
}
