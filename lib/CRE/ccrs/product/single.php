<?php
/**
 * View a Single Product
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT * FROM product WHERE license_id = :l0 AND id = :s0';
$arg = [
	':l0' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	':s0' => $ARG['id'],
];

$res = $dbc->fetchRow($sql, $arg);
if (empty($res['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [],
	], 404);
}

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
]);
