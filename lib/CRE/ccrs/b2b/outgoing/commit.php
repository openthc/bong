<?php
/**
 * Commit the B2B
 *
 * SPDX-License-Identifier: MIT
 */

// require_once('/opt/openthc/bong/vendor/openthc/cre-adapter/lib/CCRS/B2B.php');

$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

// I Have to Load the Object from our Database
$dbc = _dbc();

$b2b = $dbc->fetchRow('SELECT * FROM b2b_outgoing WHERE source_license_id = :l0 AND id = :b1', [
	':l0' => $_SESSION['License']['id'],
	':b1' => $ARG['id'],
]);
if (empty($b2b['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [],
	], 404);
}

$b2b['data'] = json_decode($b2b['data'], true);

$req_ulid = _ulid();
$req_code = sprintf('MANIFEST UPLOAD %s', $req_ulid);

$b2b_helper = new \OpenTHC\CRE\CCRS\B2B();
$csv_name = sprintf('Manifest_%s_%s.csv', $cre_service_key, $req_ulid);
$csv_temp = $b2b_helper->create_outgoing_csv($b2b['data']['@source'], $req_ulid);

$csv_blob = stream_get_contents($csv_temp);

$rec = [];
$rec['id'] = $req_ulid;
$rec['license_id'] = $_SESSION['License']['id'];
$rec['name'] = $req_code;
$rec['source_data'] = json_encode([
	'name' => $csv_name,
	'data' => $csv_blob
]);
$dbc->insert('log_upload', $rec);

// Now it just needs the AUTH & PUSH Wrapper not this whole thing
// $cmd = [];
// $cmd[] = sprintf('%s/bin/cre-ccrs-upload.php b2b-outgoing-manifest', APP_ROOT);
// $cmd[] = escapeshellarg($_SESSION['License']['id']);
// $cmd[] = escapeshellarg($b2b['id']);
// $cmd[] = '2>&1';
// $cmd[] = sprintf('>%s/var/ccrs-upload-%s-%s', APP_ROOT, $_SESSION['License']['id'], $b2b['id']);
// $cmd[] = '&';
// $cmd = implode(' ', $cmd);

// syslog(LOG_DEBUG, $cmd);

return $RES->withJSON([
	'data' => [
		'req' => $req_ulid,
		'b2b' => $b2b['id'],
		// 'csv' =>
	],
	'meta' => [],
]);
