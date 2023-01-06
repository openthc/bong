<?php
/**
 * Create Upload for Inventory Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

$dbc = _dbc();

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$license_id = array_shift($argv);
$License = _load_license($dbc, $license_id);

// CSV Data
$req_ulid = _ulid();
$csv_data = [];
$csv_data[] = [ '-canary-', '-canary-', '-canary-', '-canary-', '0', '0', '0', 'FALSE', "INVENTORY UPLOAD $req_ulid", '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
$csv_head = explode(',', 'LicenseNumber,Strain,Area,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$csv_name = sprintf('Inventory_%s_%s.csv', $cre_service_key, $req_ulid);
$col_size = count($csv_head);

$sql = <<<SQL
SELECT *
FROM lot
WHERE license_id = :l0
SQL;

$res_inventory = $dbc->fetchAll($sql, [ ':l0' => $License['id'] ]);
foreach ($res_inventory as $inv) {

	$inv_data = json_decode($inv['data'], true);
	$inv_source = $inv_data['@source'];

	$dtC = new DateTime($inv['created_at']);
	$dtC->setTimezone($tz0);

	$dtU = new DateTime($inv['updated_at']);
	$dtU->setTimezone($tz0);

	$command = 'INSERT';
	switch ($inv['stat']) {
		case '100':
			$command = 'UPDATE';
			break;
		case '404':
			$command = 'INSERT';
			break;
		case '200':
			// SKIP
			continue 2; // foreach
			break;
		case '410':
			$command = 'DELETE';
			break;
	}

	// Insert
	$row = [
		$License['code']
		, substr($inv_source['variety']['name'], 0, 50)
		, $inv_source['section']['name']
		, substr($inv_source['product']['name'], 0, 75)
		, sprintf('%0.2f', $inv_source['qty_initial'])
		, sprintf('%0.2f', $inv_source['qty'])
		, 0
		, 'FALSE'
		, $inv['id']
		, '-system-'
		, $dtC->format('m/d/Y')
		, '-system-'
		, $dtU->format('m/d/Y')
		, $command
	];

	$csv_data[] = $row;

}

$row_size = count($csv_data);

$csv_temp = fopen('php://temp', 'w');
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
}
fseek($csv_temp, 0);

// Upload
_upload_to_queue_only($License, $csv_name, $csv_temp);

unset($csv_temp);
