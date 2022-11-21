<?php
/**
 * Single Inventory Item Detail
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT * FROM lot WHERE license_id = :l0 AND id = :i0';
$arg = [
	':l0' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	':i0' => $ARG['id'],
];

$res = $dbc->fetchRow($sql, $arg);

if (empty($res['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'sql' => $sql,
			'arg' => $arg,
		],
	], 404);
}

$res['data'] = json_decode($res['data'], true);

$res = array_merge($res, $res['data']);
unset($res['data']);

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
]);
