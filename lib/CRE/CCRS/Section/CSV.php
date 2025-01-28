<?php
/**
 * Create Upload for Section Data
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Section;

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
	function create($force=false)
	{
		// Check Cache
		$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
			'license' => $this->_License['id'],
			'object' => 'section',
			'force' => $force
		]);

		if (202 == $uphelp->getStatus()) {
			return;
		}

		$dbc = _dbc();

		$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($api_code, 'section');

		$tz0 = new \DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));

		// Get Data
		$csv_data = [];
		$sql = <<<SQL
		SELECT section.*
		FROM section
		WHERE license.id = :l0
		  AND stat IN (100, 102, 200, 404)
		ORDER BY id
		SQL;
		$arg = [ ':l0' => $this->_License['id'] ];

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
			$uphelp->setStatus(202);
			return;
		}

		$csv_name = $csv->getName();
		$csv_temp = $csv->getData('stream');

		_upload_to_queue_only($this->_License, $csv_name, $csv_temp);

		$uphelp->setStatus(102);
	}

}
