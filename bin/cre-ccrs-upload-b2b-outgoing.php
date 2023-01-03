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

// CSV Data
$req_ulid = _ulid();
$csv_data = [];
$csv_data[] = [ '-canary-', '-canary-', "B2B_OUTGOING UPLOAD $req_ulid", '-canary-', '0', '', '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
$csv_head = explode(',', 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,SalesTax,OtherTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$csv_name = sprintf('Sale_%s_%s.csv', $cre_service_key, $req_ulid);
$col_size = count($csv_head);


$sql = <<<SQL
SELECT b2b_outgoing.*, b2b_outgoing_item.*
FROM b2b_outgoing
JOIN b2b_outgoing_item ON b2b_outgoing.id = b2b_outgoing_item.b2b_outgoing_id
WHERE b2b_outgoing.source_license_id = :l0
SQL;

$arg = [ ':l0' => $License['id'] ];

$res_b2b_outgoing_item = $dbc->fetchAll($sql, $arg);
foreach ($res_b2b_outgoing_item as $b2b_outgoing_item) {

	$dtC = new DateTime($b2b_outgoing_item['created_at']);
	$dtU = new DateTime($b2b_outgoing_item['updated_at']);

	$csv_data[] = [
		$License['code'] // LicenseNumber
		, $b2b_outgoing_item['target_license_code'] // SoldToLicenseNumber
		, $b2b_outgoing_item['source_lot_id'] // InventoryExternalIdentifier
		, '' // PlantExternalIdentifier
		, 'Wholesale' // SaleType
		, $dtC->format('m/d/Y') // SaleDate
		, $b2b_outgoing_item['unit_count'] // Quantity
		, $b2b_outgoing_item['unit_price'] // UnitPrice
		, '0' // Discount
		, '0' // SalesTax
		, '0' // OtherTax
		, $b2b_outgoing_item['manifest_guid'] // SaleExternalIdentifier
		, $b2b_outgoing_item['manifest_item_id'] // SaleDetailExternalIdentifier
		, '-system-' // CreatedBy
		, $dtC->format('m/d/Y') // CreatedDate
		, '-system-' // UpdatedBy
		, $dtU->format('m/d/Y') // UpdatedDate
		, 'UPDATE' // OPERATION
	];

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
