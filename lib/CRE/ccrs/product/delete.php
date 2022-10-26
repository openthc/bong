<?php
/**
 * Delete a Product
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

// Object Exists?
$sql = 'SELECT id, license_id FROM product WHERE id = :p0';
$arg = [ ':p0' => $ARG['id'] ];
$chk = $dbc->fetchRow($sql, $arg);
if (empty($chk['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'detail' => 'Not Found'
		],
	], 404);
}

// Access?
if (empty($chk['license_id']) != $RES->getAttribute('license_id')) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'detail' => 'Access Denied'
		],
	], 403);
}

// Delete
$sql = 'UPDATE product SET stat = 410 WHERE license_id = :l0 AND id = :p0';
$arg = [
	':l0' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	':p0' => $ARG['id'],
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
