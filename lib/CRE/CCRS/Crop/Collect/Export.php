<?php
/**
 * Create Upload for Crop
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Crop;

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

	function create($force=false)
	{
		// Check Cache
		$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
			'license' => $this->_License['id'],
			'object' => 'crop',
			'force' => $force
		]);
		if (202 == $uphelp->getStatus()) {
			return;
		}

		$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($api_code, 'crop');

		// Lets Go!
		$dbc = _dbc();

		$arg = [
			':l0' => $this->_License['id']
		];

		$sql = <<<SQL
		SELECT *
		FROM crop
		WHERE license_id = :l0
		  AND stat IN (100, 102, 200, 404)
		ORDER BY id
		SQL;

		$res_source = $dbc->fetchAll($sql, $arg);

		foreach ($res_source as $rec_source) {

			$cmd = '';
			switch ($rec_source['stat']) {
				case 100:
				case 404:
					$cmd = 'INSERT';
					// $this->setRecord();
					$dbc->query('UPDATE crop SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $rec_source['id'],
					]);
					break;
				case 102:
					// Skip, 102 should resolve to a 200 or 400 level response
					$cmd = 'INSERT';
					$dbc->query('UPDATE crop SET stat = 200, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $rec_source['id'],
					]);
					break;
				case 200:
					$cmd = 'UPDATE';
					$dbc->query('UPDATE crop SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :x0', [
						':x0' => $rec_source['id'],
					]);
					break;
				case 202:
					// Fully Uploaded
					$cmd = 'UPDATE';
					break;
				case 400:
				case 403:
					// Ignore
					// $cmd = 'UPDATE';
					break;
				case 410:
				case 666:
					// $cmd = 'DELETE';
					break;
				default:
					throw new \Exception("Invalid Crop Stat '{$rec_source['stat']}'");
			}

			if (empty($cmd)) {
				continue;
			}

			$rec_export = $this->createRecord($rec_source, $cmd);

			$csv->addRow($rec_export);

		}

		// No Data, In Sync
		if ($csv->isEmpty()) {
			$uphelp->setStatus(202);
			return;
		}

		$csv_name = $csv->getName();
		$csv_temp = $csv->getData('stream');

		\OpenTHC\Bong\CRE\CCRS\Upload::enqueue($this->_License, $csv_name, $csv_temp);

		$uphelp->setStatus(102);

	}

	/**
	 * Format from OpenTHC to CCRS
	 */
	function createRecord($rec_source, $cmd)
	{
		$rec_source['data'] = json_decode($rec_source['data'], true);

		$dtC = new \DateTime($rec_source['created_at'], $this->_tz0);
		$dtC->setTimezone($this->_tz0);
		$created_at_f = $dtC->format('m/d/Y');

		$dtU = new \DateTime($rec_source['updated_at'], $this->_tz0);
		$dtU->setTimezone($this->_tz0);
		$updated_at_f = $dtU->format('m/d/Y');

		$collect_at_f = '';
		if ( ! empty($rec_source['data']['@source']['collect_at'])) {
			$dtH = new \DateTime($rec_source['data']['@source']['collect_at'], $this->_tz0);
			$dtH->setTimezone($this->_tz0);
			$collect_at_f = $dtH->format('m/d/Y');
		}

		$obj = [
			$this->_License['code']
			, $rec_source['id']
			, CCRS::sanatize($rec_source['data']['@source']['section']['name'], 50)
			, CCRS::sanatize($rec_source['data']['@source']['variety']['name'], 100)
			, $rec_source['data']['@source']['source']['type'] ?: 'Clone' // PlantSource = Clone, Seed
			, 'Growing'    // PlantState = Growing, PartiallyHarvested, Quarantined, Inventory, Drying, Harvested, Destroyed, Sold
			, 'Vegetative' // GrowthStage = Immature, Vegetative, Flowering
			, '' // $x['source_plant_id'] // MotherPlantExternalIdentifier
			, '' // $x['raw_collect_date'] // HarvestDate
			, 'TRUE' // IsMotherPlant
			, $rec_source['id']
			, '-system-'
			, $dtC->format('m/d/Y')
			, '' // '-system-'
			, '' // $dtU->format('m/d/Y')
			, $cmd
		];

		if (empty($rec_source['data']['@source']['growthphase'])) {
			$rec_source['data']['@source']['growthphase'] = 'Growing';
		}

		switch ($rec_source['data']['@source']['growthphase']) {
		case 'Flowering':
			$obj[5] = 'Growing';
			$obj[6] = 'Flowering';
			break;
		case 'Growing':
			$obj[5] = 'Growing';
			$obj[6] = 'Vegetative';
			break;
		case 'Harvested':
			$obj[5] = 'Harvested';
			$obj[6] = 'Flowering';
			$obj[8] = $collect_at_f;
			// if empty, shit the bed?
			break;
		case 'Inventory':
			$obj[5] = 'Inventory';
			break;
		case 'Seedling':
			$obj[5] = 'Growing';
			$obj[6] = 'Immature';
			break;
		default:
			var_dump($rec_source);
			throw new \Exception('What to do?');
		}

		// Use OpenTHC Status
		switch ($rec_source['data']['@source']['stat']) {
		case 200:
		case 204:
			// Growing, Trust Above
			break;
		case 410:
			$obj[5] = 'Destroyed';
			$obj[6] = 'Vegetative';
			break;
		}

		// Add Contact ULID?
		switch ($cmd) {
		case 'DELETE':
		case 'UPDATE':
			$obj[13] = '-system-';
			$obj[14] = $dtU->format('m/d/Y');
			break;
		}

		return $obj;

	}

}
