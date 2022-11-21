<?php
/**
 * Inventory Search
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

// $res = $dbc->fetchAll("SELECT id, hash, updated_at, data->'result' AS result FROM section ORDER BY updated_at DESC");
$sql = 'SELECT * FROM lot {WHERE} ORDER BY updated_at DESC';
// $sql = 'SELECT id, stat, hash, updated_at FROM section {WHERE} ORDER BY updated_at DESC';

$sql_param = [];
$sql_where = [];

// $sql_where[] = 'license_id = :l0';
// $sql_param[':l0'] = $_SESSION['License']['id'];

if ( ! empty($_GET['q'])) {
	$sql_where[] = 'data::text LIKE :q23';
	$sql_param[':q23'] = sprintf('%%%s%%', $_GET['q']);
}

if (count($sql_where)) {
	$sql_where = implode(' AND ', $sql_where);
	$sql = str_replace('{WHERE}', sprintf(' WHERE %s', $sql_where), $sql);
} else {
	$sql = str_replace('{WHERE}', '', $sql);
}

$res = $dbc->fetchAll($sql, $sql_param);
$res['sql'] = $sql;

return $RES->withJSON($res, 200, JSON_PRETTY_PRINT);

// $dbc = $REQ->getAttribute('dbc');
// $res = $dbc->fetchAll('SELECT id, hash, updated_at FROM lot ORDER BY updated_at DESC');

// return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
