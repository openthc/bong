<?php
/**
 * Search for Variety
 *
 * SPDX-License-Identifier: MIT
 */

$q = trim($_GET['q']);
if (empty($q)) {

	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'detail' => 'Invalid Request; Parameter "q" must be provided" [CVS-014]'
		],
	], 400);

}

// Search
$sql = <<<SQL
SELECT *
FROM variety
WHERE name = :v0 OR name LIKE :v1
ORDER BY name
LIMIT 25
SQL;
$arg = [];
$arg[':v0'] = $q;
$arg[':v1'] = sprintf('%%%s%%', $arg[':v0']);

$dbc = $REQ->getAttribute('dbc');

$res = $dbc->fetchAll($sql, $arg);

return $RES->withJSON([
	'data' => $res,
	'meta' => [],
], 201);
