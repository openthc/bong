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
	if (202 == $uphelp->getStatus()) {
		return 0;
	}

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));

	$dbc = _dbc();
	$License = _load_license($dbc, $cli_args['--license']);

	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

	$sql = <<<SQL
	SELECT *
	FROM crop
	WHERE license_id = :l0
	  AND stat IN (100, 102, 200)
	ORDER BY stat ASC, updated_at ASC
	LIMIT 2500
	SQL;
	$res_crop = $dbc->fetchAll($sql, [
		':l0' => $License['id']
	]);

	// Build CSV
	$req_ulid = _ulid();
	$csv_name = sprintf('Plant_%s_%s.csv', $cre_service_key, $req_ulid);
	$csv_temp = fopen('php://temp', 'w');
	// $csv_temp = fopen('php://stdout', 'w');

	$csv_head = explode(',', 'LicenseNumber,PlantIdentifier,Area,Strain,PlantSource,PlantState,GrowthStage,MotherPlantExternalIdentifier,HarvestDate,IsMotherPlant,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);

	$csv_data = [];
	$csv_data[] = [ '-canary-', "CROP UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-', '-canary-', '-canary-', '-canary-', date('m/d/Y'), 'FALSE', '-canary-', 'OpenTHC', date('m/d/Y'), '' ,'', 'UPDATE' ];

	foreach ($res_crop as $x) {

		$x['data'] = json_decode($x['data'], true);

		$cre_op = 'INSERT';
		if ($x['stat'] == 200) {
			$cre_op = 'UPDATE';
		}

		$dtC = new DateTime($x['created_at'], $tz0);
		$dtU = new DateTime($x['updated_at'], $tz0);

		$obj = [
			$License['code']
			, $x['id']
			, CCRS::sanatize($x['data']['@source']['section']['name'], 50)
			, CCRS::sanatize($x['data']['@source']['variety']['name'], 100)
			, $x['data']['@source']['source']['type'] ?: 'Clone' // PlantSource  // Clone, Seed
			, 'Growing' // PlantState // Growing, PartiallyHarvested, Quarantined, Inventory, Drying, Harvested, Destroyed, Sold
			, 'Vegetative' // GrowthStage // Immature, Vegetative, Flowering
			, '' // $x['source_plant_id'] // MotherPlantExternalIdentifier
			, '' // $x['raw_collect_date'] // HarvestDate
			, 'TRUE' // IsMotherPlant
			, $x['id']
			, '-system-'
			, $dtC->format('m/d/Y')
			, '-system-'
			, $dtU->format('m/d/Y')
			, $cre_op
		];

		switch ($x['data']['@source']['growthphase']) {
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
				$obj[8] =
				var_dump($x);
				exit;
				break;
			case 'Seedling':
				$obj[5] = 'Growing';
				$obj[6] = 'Immature';
		}

		$csv_data[] = $obj;

	}

	// No Data, In Sync
	$row_size = count($csv_data);
	if ($row_size <= 1) {
		$uphelp->setStatus(202);
		return;
	}

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

	$uphelp->setStatus(102);

}
