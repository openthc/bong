<?php
/**
 * View a Single Section
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT * FROM section WHERE license_id = :l0 AND id = :s0';
$arg = [
	':l0' => $_SESSION['License']['id'],
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
