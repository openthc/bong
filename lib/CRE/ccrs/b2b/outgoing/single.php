<?php
/**
 * Single Inventory Item Detail
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT * FROM b2b_outgoing WHERE source_license_id = :l0 AND id = :i0';
$arg = [
	':l0' => $_SESSION['License']['id'],
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
// $res = array_merge($res, $res['data']);
// unset($res['data']);

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
], 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
