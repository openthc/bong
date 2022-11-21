<?php
/**
 * Product Search Interface
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = <<<SQL
SELECT id, name, code, stat FROM license
WHERE id IN (SELECT license_id FROM product where data::text LIKE '%Integrator is not authorized%')
ORDER BY id
SQL;
$res = $dbc->fetchAll($sql);
if (count($res)) {
	__exit_text($res);
}



$sql = 'SELECT id, name, guid FROM product WHERE license_id = :l0 ORDER BY updated_at DESC';
$arg = [
	':l0' => $_SERVER['HTTP_OPENTHC_LICENSE'],
];

$res = $dbc->fetchAll($sql, $arg);

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
], 501);
