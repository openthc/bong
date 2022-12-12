<?php
/**
 * Update a Crop
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT id, license_id, data FROM crop WHERE id = :s0';
$arg = [ ':s0' => $ARG['id'] ];
$chk = $dbc->fetchRow($sql, $arg);
if (empty($chk['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'detail' => 'Not Found'
		],
	], 404);
}

if (empty($chk['license_id']) != $RES->getAttribute('license_id')) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'detail' => 'Access Denied'
		],
	], 403);
}

// Update It
$crop = json_decode($crop['data'], true);
// @todo Something other than accepting the entire request?
$crop = array_merge($crop, $_POST);
$sql = 'UPDATE crop SET name = :n0, data = :d0, updated_at = now() WHERE license_id = :l0 AND id = :s0';
$arg = [
	':l0' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	':s0' => $ARG['id'],
	':n0' => $_POST['name'],
	':d0' => json_encode($crop),
];

$ret = $dbc->query($sql, $arg);
if (1 == $ret) {
	return $RES->withJSON([
		'data' => [
			'id' => $ARG['id'],
			'name' => $_POST['name']
		],
		'meta' => [],
	]);
}

return $RES->withJSON([
	'data' => null,
	'meta' => [
		'detail' => 'Invalid Object'
	],
], 500);
