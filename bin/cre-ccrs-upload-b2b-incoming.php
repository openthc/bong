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

$req_ulid = _ulid();
$csv_data = [];
$csv_data[] = [ '-canary-', '-canary-', "B2B_INCOMING UPLOAD $req_ulid", '-canary-', 0, date('m/d/Y'), '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
$csv_head = explode(',', 'FromLicenseNumber,ToLicenseNumber,FromInventoryExternalIdentifier,ToInventoryExternalIdentifier,Quantity,TransferDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$csv_name = sprintf('InventoryTransfer_%s_%s.csv', $cre_service_key, $req_ulid);
$col_size = count($csv_head);
$csv_temp = fopen('php://temp', 'w');


$sql = <<<SQL
SELECT b2b_incoming.*,
  b2b_incoming_item.id AS b2b_incoming_item_id,
  b2b_incoming_item.name AS b2b_incoming_item_name,
  b2b_incoming_item.data AS b2b_incoming_item_data
FROM b2b_incoming
JOIN b2b_incoming_item ON b2b_incoming.id = b2b_incoming_item.b2b_incoming_id
WHERE b2b_incoming.target_license_id = :l0
SQL;
$res_b2b_incoming_item = $dbc->fetchAll($sql, [ ':l0' => $License['id'] ]);

foreach ($res_b2b_incoming_item as $x) {

	$x['data'] = json_decode($x['data'], true);
	$x['b2b_incoming_item_data'] = json_decode($x['b2b_incoming_item_data'], true);

	$dtC = new \DateTime($x['created_at']);
	$dtC->setTimezone($tz0);

	$dtU = new \DateTime($x['updated_at']);
	$dtU->setTimezone($tz0);

	$rec = [
		$x['data']['@source']['source']['code'], // FromLicenseNumber
		$x['data']['@source']['target']['code'] // ToLicenseNumber
		, $x['b2b_incoming_item_data']['@source']['source_lot']['id'] //   ['origin_lot_id'] // FromInventoryExternalIdentifier
		, $x['b2b_incoming_item_data']['@source']['target_lot']['id'] //   ['target_lot_id'] // ToInventoryExternalIdentifier
		, $x['b2b_incoming_item_data']['@source']['unit_count'] // Quantity
		, $dtC->format('m/d/Y') // date('m/d/Y', strtotime($x['created_at']))
		, $x['b2b_incoming_item_id'] // , sprintf('%s/%s', $x['b2b_sale_id'], $x['b2b_sale_item_id'])
		, '-system-'
		, $dtC->format('m/d/Y')
		, '-system-'
		, $dtU->format('m/d/Y')
		, 'UPDATE'
	];

	$csv_data[] = $rec;
}

$output_row_count = count($csv_data);

\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $output_col, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $output_col, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $output_row_count ], $output_col, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
}

// Upload
fseek($csv_temp, 0);

_upload_to_queue_only($License, $csv_name, $csv_temp);

unset($csv_temp);
