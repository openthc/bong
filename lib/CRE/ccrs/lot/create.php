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
	'id' => $_POST['id'] ?: substr(_ulid(), 0, 16),
	'license_id' => $_SESSION['License']['id'],
	'data' => json_encode($_POST),
];

$ret = $dbc->insert('lot', $rec);

return $RES->withJSON([
	'data' => $rec,
	'meta' => [],
], 201);
