<?php
/**
 * Create Upload for B2B Incoming Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

$dbc = _dbc();

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$license_id = array_shift($argv);
$License = _load_license($dbc, $license_id);

$b2b_outgoing_id = array_shift($argv);
$Manifest = $dbc->fetchRow('SELECT * FROM b2b_outgoing WHERE id = :b2b0', [ ':b2b0' => $b2b_outgoing_id ]);
$Manifest['data'] = json_decode($Manifest['data'], true);
// var_dump($Manifest['data']['@source']);

$dtC = new DateTime($Manifest['created_at']);
$dtU = new DateTime($Manifest['updated_at']);

$req_ulid = _ulid();

$csv_data = [];

$sql = <<<SQL
SELECT b2b_outgoing.*, b2b_outgoing_item.*
FROM b2b_outgoing
JOIN b2b_outgoing_item ON b2b_outgoing.id = b2b_outgoing_item.b2b_outgoing_id
WHERE b2b_outgoing.source_license_id = :l0 AND b2b_outgoing.id = :b2b0
SQL;

$arg = [
	':l0' => $License['id'],
	':b2b0' => $Manifest['id'],
];

$res_b2b_outgoing_item = $dbc->fetchAll($sql, $arg);
foreach ($res_b2b_outgoing_item as $b2b_outgoing_item) {

	$b2b_outgoing_item['data'] = json_decode($b2b_outgoing_item['data'], true);
	// var_dump($b2b_outgoing_item['data']); exit;
	$src = $b2b_outgoing_item['data']['@source'];
	// var_dump($src); exit;

	$chk = $dbc->fetchRow('SELECT id FROM lot WHERE license_id = :l0 AND id = :i0', [
		':l0' => $License['id'],
		':i0' => $src['lot']['id']
	]);
	if (empty($chk['id'])) {
		echo "INVENTORY LOST: {$src['lot']['id']}\n";
	}

	$rec =  [
		$src['lot']['id'] // InventoryExternalIdentifier
		, '' // PlantExternalIdentifier
		, $src['unit_count'] // Quantity
		, $src['product']['uom'] ?: 'GRAM' // UOM
		, '0' // sprintf('%0.2f', 1) // WeightPerUnit
		, '1' // ServingsPerUnit
		, $b2b_outgoing_item['id'] // ExternalIdentifier
		, '-system-' // CreatedBy
		, $dtC->format('m/d/Y') // CreatedDate
		, '-system-' // UpdatedBy
		, $dtU->format('m/d/Y') // UpdatedDate
		, 'INSERT' // OPERATION
	];

	$csv_data[] = $rec;

}

$row_size = count($csv_data);

$csv_temp = fopen('php://temp', 'w');


// CSV Data
$req_ulid = _ulid();
$csv_name = sprintf('Manifest_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_head = explode(',', 'InventoryExternalIdentifier,PlantExternalIdentifier,Quantity,UOM,WeightPerUnit,ServingsPerUnit,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$col_size = count($csv_head);

\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy', 'OpenTHC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'ExternalManifestIdentifier', $Manifest['id'] ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'Header Operation','INSERT' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'TransportationType', 'REGULAR' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'OriginLicenseNumber', $Manifest['data']['@source']['source_license']['code'] ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'OriginLicenseePhone', sprintf('855-976-9333', $Manifest['data']['@source']['source_license']['code']) ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'OriginLicenseeEmailAddress', sprintf('code+%s@openthc.com', $req_ulid) ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'TransportationLicenseNumber', '' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'DriverName', 'DRIVER NAME' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'DepartureDateTime', '01/02/2023 10:00:00 AM' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'ArrivalDateTime', '01/02/2023 10:00:00 AM' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'VIN #', '1234567890' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'VehiclePlateNumber', '123 ABC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'VehicleModel', 'Delorean' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'VehicleMake', 'AMC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'VehicleColor', 'Grey' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'DestinationLicenseNumber', $Manifest['data']['@source']['target_license']['code'] ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'DestinationLicenseePhone', '12345687890' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'DestinationLicenseeEmailAddress', 'code+target@openthc.com' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));

foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
}
fseek($csv_temp, 0);

// fpassthru($csv_temp);

// Upload
// fseek($csv_temp, 0);
_upload_to_queue_only($License, $csv_name, $csv_temp);

unset($csv_temp);
