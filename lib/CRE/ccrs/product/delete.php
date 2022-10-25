<?php
/**
 * Delete a Product
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'UPDATE product SET stat = 410 WHERE license_id = :l0 AND id = :s0';
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
