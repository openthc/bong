<?php
/**
 * Update a Section
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

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

if (empty($chk['license_id']) != $_SESSION['License']['id']) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'detail' => 'Access Denied'
		],
	], 403);
}

// Update It
$sql = 'UPDATE section SET name = :n0, updated_at = now() WHERE license_id = :l0 AND id = :s0';
$arg = [
	':l0' => $_SESSION['License']['id'],
	':s0' => $ARG['id'],
	':n0' => $_POST['name'],
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
