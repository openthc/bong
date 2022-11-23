<?php
/**
 * Product Search Interface
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

// if ($_GET['e'])
if (isset($_GET['e'])) {

	$sql = <<<SQL
	SELECT id, name, code, stat FROM license
	WHERE id IN (SELECT license_id FROM product where data::text LIKE '%Integrator is not authorized%')
	ORDER BY id
	SQL;

	$res = $dbc->fetchAll($sql);

	if (count($res)) {
		__exit_text($res);
	}

}

// $dbc = $REQ->getAttribute('dbc');
// $res = $dbc->fetchAll("SELECT id, license_id, stat, hash, updated_at FROM product ORDER BY updated_at DESC");
// return $RES->withJSON($res);


$sql = <<<SQL
SELECT id, name
FROM product
WHERE license_id = :l0
ORDER BY updated_at DESC
SQL;

$arg = [
	':l0' => $_SESSION['License']['id'],
];

$res = $dbc->fetchAll($sql, $arg);

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
], 501);
