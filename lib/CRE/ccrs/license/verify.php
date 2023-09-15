<?php
/**
 * Verify a License in CCRS with a dummy Section
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
if (empty($License['id'])) {
	echo "Invalid License '{$ARG['id']}' [CCU-071]\n";
	exit(1);
}

$dbc->query('UPDATE license SET stat = 102 WHERE id = :l0', [ ':l0' => $License['id'] ]);

$req_ulid = _ulid();
$req_code = "SECTION UPLOAD $req_ulid";

$csv_data = [];
$csv_data[] = [ '-canary-', $req_code, 'FALSE', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
$csv_data[] = [
	$License['code']
	, 'OPENTHC SECTION PING'
	, 'FALSE'
	, 'OPENTHC SECTION PING'
	, '-system-'
	, date('m/d/Y')
	, '-system-'
	, date('m/d/Y')
	, 'DELETE'
];

$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
$csv_name = sprintf('Area_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_head = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
$col_size = count($csv_head);
$row_size = count($csv_data);
$csv_temp = fopen('php://temp', 'w');

// Output
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
foreach ($csv_data as $row) {
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
}
fseek($csv_temp, 0);

// Add to Database
$rec = [];
$rec['id'] = $req_ulid;
$rec['license_id'] = $License['id'];
$rec['name'] = $req_code;
$rec['source_data'] = json_encode([
	'name' => $csv_name,
	'data' => stream_get_contents($csv_temp)
]);

$dbc->insert('log_upload', $rec);

return $RES->withJSON([
	'data' => $req_ulid,
	'meta' => [],
], 201);
