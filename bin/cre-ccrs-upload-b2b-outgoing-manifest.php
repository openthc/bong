<?php
/**
 * Create Upload for B2B Incoming Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

$dbc = _dbc();

$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$license_id = array_shift($argv);
$License = _load_license($dbc, $license_id);

$b2b_outgoing_id = array_shift($argv);
$Manifest = $dbc->fetchRow('SELECT * FROM b2b_outgoing WHERE id = :b2b0', [ ':b2b0' => $b2b_outgoing_id ]);
$Manifest['data'] = json_decode($Manifest['data'], true);
$B2B_Blob = $Manifest['data']['@source'];
// var_dump($B2B_Blob);
// exit;

$dtC = new \DateTime($Manifest['created_at']);
$dt0 = new \DateTime('2023-01-09');
if ($dtC <= $dt0) {
	echo "We don't Manifest OLD ones\n";
	exit(1);
}


$req_ulid = _ulid();
$csv_name = sprintf('Manifest_%s_%s.csv', $cre_service_key, $req_ulid);

$b2b_helper = new \OpenTHC\CRE\CCRS\B2B();
$csv_temp = $b2b_helper->create_outgoing_csv($B2B_Blob, $req_ulid);

_upload_to_queue_only($License, $csv_name, $csv_temp);
