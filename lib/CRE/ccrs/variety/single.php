<?php
/**
 * View a Single Variety
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT * FROM variety WHERE name = :v0';
$arg = [
	':v0' => $ARG['id'],
];

$res = $dbc->fetchRow($sql, $arg);

if (empty($res['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [],
	], 404);
}

$res['data'] = json_decode($res['data'], true);

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
]);
