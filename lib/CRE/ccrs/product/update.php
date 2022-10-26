<?php
/**
 * Update a Product
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT id, license_id FROM product WHERE id = :o1';
$arg = [ ':o1' => $ARG['id'] ];
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

// Delete It
$sql = 'UPDATE product SET name :n0, updated_at = now() WHERE license_id = :l0 AND id = :o1';
$arg = [
	':l0' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	':o1' => $ARG['id'],
	':n0' => $_POST['name'],
];

$ret = $dbc->query($sql, $arg);
if (1 == $ret) {
	return $RES->withJSON([
		'data' => [
			'stat' => 410,
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
