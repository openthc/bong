<?php
/**
 * Inventory Create
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

if (empty($_POST['product']) && !empty($_POST['product_id'])) {
	$_POST['product'] = [
		'id' => $_POST['product_id'],
	];
}
if (empty($_POST['variety']) && !empty($_POST['variety_id'])) {
	$_POST['variety'] = [
		'id' => $_POST['variety_id'],
	];
}
if (empty($_POST['section']) && !empty($_POST['section_id'])) {
	$_POST['section'] = [
		'id' => $_POST['section_id'],
	];
}


$rec = [
	'id' => _ulid(),
	'license_id' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	'data' => json_encode($_POST),
	// 'qty' => $_POST['qty'],
	// 'variety_id' => $_POST['variety_id'],
	// 'product_id' => $_POST['product_id'],
];

$ret = $dbc->insert('lot', $rec);

return $RES->withJSON([
	'data' => $rec,
	'meta' => [],
], 201);

// Array
// (
//     [source] => Array
//         (
//             [0] => Array
//                 (
//                     [id] => 01FY7P6SXXTCY743
//                     [qty] => 100.00
//                 )
//         )
//     [product_id] => 01G81R70JTF6KHA43Z8FY3HGKC
//     [qty] => 100
//     [variety] => WAJWPP.ST6ZSN
//     [section] => 01G899GGB0QWSG6WZ2APV9ECNB
// )
