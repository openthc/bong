<?php
/**
 * Create Upload for Section Data
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Section;

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
		$this->_cre_config = \OpenTHC\CRE::getConfig('usa-wa');
		$this->_tz0 = new \DateTimezone($this->_cre_config['tz']);
		$this->_dt0 = new \DateTime('now', $this->_tz0);
	}

	/**
	 *
	 */
	function create($force=false)
	{
		// Check Cache
		$status = new \OpenTHC\Bong\CRE\CCRS\Status($this->_License['id'], 'section');
		$chk = $status->getStat();
		switch ($chk) {
			case 202:
				return;
		}

		$dbc = _dbc();

		$api_code = $this->_cre_config['service-sk'];
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($api_code, 'section');

		// Get Data
		$csv_data = [];
		$sql = <<<SQL
		SELECT section.*
		FROM section
		WHERE license_id = :l0
		  AND stat IN (100, 102, 200, 404)
		ORDER BY id
		SQL;
		$arg = [ ':l0' => $this->_License['id'] ];

		$res_section = $dbc->fetchAll($sql, $arg);
		foreach ($res_section as $section) {

			$dtC = new \DateTime($section['created_at'], $this->_tz0);
			$dtC->setTimezone($this->_tz0);
			$dtU = new \DateTime($section['updated_at'], $this->_tz0);
			$dtU->setTimezone($this->_tz0);

			$cmd = '';
			switch ($section['stat']) {
				case 100:
				case 404:
					$cmd = 'INSERT';
					$dbc->query('UPDATE section SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $section['id'],
					]);
					break;
				case 102:
					$cmd = 'INSERT';
					$dbc->query('UPDATE section SET stat = 200, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $section['id'],
					]);
					break;
				case 200:
					$cmd = 'UPDATE';
					$sql = 'UPDATE section SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :s0';
					$dbc->query($sql, [
						':s0' => $section['id'],
					]);
					break;
				case 400:
				case 403:
					// Ignore
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
				$this->_License['code']
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
			$status->setPush(202);
			return;
		}

		$csv_name = $csv->getName();
		$csv_temp = $csv->getData('stream');

		\OpenTHC\Bong\CRE\CCRS\Upload::enqueue($this->_License, $csv_name, $csv_temp);

		$status->setPush(102);
	}

}
