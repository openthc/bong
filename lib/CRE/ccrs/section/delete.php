<?php
/**
 * Delete a Section
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

// Object Exists?
$sql = 'SELECT id, license_id FROM section WHERE id = :s0';
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
$sql = 'UPDATE section SET stat = 410, updated_at = now() WHERE license_id = :l0 AND id = :s0';
$arg = [
	':l0' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	':s0' => $ARG['id'],
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
