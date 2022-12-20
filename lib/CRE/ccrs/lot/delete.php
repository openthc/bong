<?php
/**
 * Delete a Lot
 *
 * @todo 2-step Destruction process to be followed by setting the Lot to stat=423
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

// Object Exists?
$sql = 'SELECT id, license_id, stat FROM product WHERE id = :p0';
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
if (empty($chk['license_id']) != $_SESSION['License']['id']) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'detail' => 'Access Denied'
		],
	], 403);
}

// Delete
$sql = 'UPDATE lot SET stat = 410 WHERE license_id = :l0 AND id = :p0';
$arg = [
	':l0' => $_SESSION['License']['id'],
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
