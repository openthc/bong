<?php
/**
 * Single Company
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT * FROM company WHERE id = :c0';
$arg = [
	':c0' => $ARG['id'],
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

// $res['data'] = json_decode($res['data'], true);
// $res = array_merge($res, $res['data']);
// unset($res['data']);

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
]);
