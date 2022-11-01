<?php
/**
 * CCRS Update is not Possible
 *
 * SPDX-License-Identifier: MIT
 */

$_POST['name'] = trim($_POST['name']);

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT id, stat FROM variety WHERE id = :v0 OR name = :v0';
$arg = [
	':v0' => $_POST['name']
];
$chk = $dbc->fetchRow($sql, $arg);

// Create if Necessary?
if (empty($chk['id'])) {
	return require_once(__DIR__ . '/create.php');
}

return $RES->withJSON([
	'data' => $chk,
	'meta' => [],
], 200);
