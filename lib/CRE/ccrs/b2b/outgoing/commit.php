<?php
/**
 * Commit the B2B
 *
 * SPDX-License-Identifier: MIT
 */

require_once('/opt/openthc/bong/vendor/openthc/cre-adapter/lib/CCRS/B2B.php');

// I Have to Load the Object from our Database
$dbc = _dbc();

$b2b = $dbc->fetchRow('SELECT * FROM b2b_outgoing WHERE source_license_id = :l0 AND id = :b1', [
	':l0' => $_SESSION['License']['id'],
	':b1' => $ARG['id'],
]);

$b2b['data'] = json_decode($b2b['data'], true);

// $out = upload_b2b_to_self($ARG['id']);
// var_dump($out);
// $out = upload_csv();

$b2b_helper = new \OpenTHC\CRE\CCRS\B2B();
$csv_temp = $b2b_helper->create_outgoing_csv($b2b['data']['@source']);

return $RES->withJSON([
	'data' => stream_get_contents($csv_temp),
	'meta' => [],
]);

// Take the Data and Generate the CSV
// function upload_b2b_to_self($b2b_id)
// {
// 	// $argv = [];
// 	// $argv[] = __FILE__;
// 	// $argv[] = 'b2b-outgoing-manifest';
// 	// $argv[] = $_SESSION['License']['id'];
// 	// $argv[] = $b2b_id;

// 	// ob_start();

// 	// require_once(APP_ROOT . '/bin/cre-ccrs-upload.php');
// 	// // require_once(APP_ROOT . '/bin/cre-ccrs-upload-b2b-outgoing-manifest.php');

// 	// $ret = ob_get_clean();
// }

// // Upload the CSV
// function upload_csv($csv_file)
// {
// 	// Something
// }
