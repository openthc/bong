<?php
/**
 * Create Upload for Crop Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

$dbc = _dbc();

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');


$license_id = array_shift($argv);
$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $license_id ]);
if (empty($License['id'])) {
	echo "Invalid License\n";
	exit(1);
}

$res_crop = $dbc->fetchAll("SELECT * FROM crop WHERE license_id = :l0 AND stat = 200", [
	':l0' => $License['id']
]);


// Build CSV
$req_ulid = _ulid();
$csv_name = sprintf('plant_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_temp = fopen('php://temp', 'w');
// $csv_temp = fopen('php://stdout', 'w');

$csv_head = explode(',', 'LicenseNumber,PlantIdentifier,Area,Strain,PlantSource,PlantState,GrowthStage,HarvestCycle,MotherPlantExternalIdentifier,HarvestDate,IsMotherPlant,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$col_size = count($csv_head);

$csv_data = [];
$csv_data[] = [ '-canary-', "CROP UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-', '-canary-', '-canary-', '-canary-', '-canary-', date('m/d/Y'), 'FALSE', '-canary-', 'OpenTHC', date('m/d/Y'), '' ,'', 'UPDATE' ];

foreach ($res_crop as $x) {

	$dtC = new DateTime($x['created_at'], $tz0);
	$dtU = new DateTime($x['updated_at'], $tz0);

	$csv_data[] = [
		$License['code']
		, $x['guid']
		, 'Main Section' // $x['section_name']
		, substr($x['variety_name'], 0, 50)
		, 'Clone' // $x['source'] // What?
		, 'Growing' // $x['plant_state'] // What?
		, 'Vegetative' // $x['growth_stage']
		, '3' // $x['harvest_cycle']
		, $x['source_plant_id'] // MotherPlantExternalIdentifier
		, $x['raw_collect_date'] // HarvestDate
		, 'TRUE' // IsMotherPlant
		, $x['guid']
		, '-system-'
		, date('m/d/Y')
		, '-system-'
		, date('m/d/Y')
		, 'UPDATE'
	];
}
$output_row_count = count($csv_data);
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $output_row_count ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
}

// Upload
fseek($csv_temp, 0);

_upload_to_queue_only($License, $csv_name, $csv_temp);

unset($csv_temp);
