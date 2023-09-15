<?php
/**
 * Single Inventory Item Detail
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT * FROM b2b_outgoing WHERE id = :i0';
$arg = [
	// ':l0' => $_SESSION['License']['id'],
	':i0' => $ARG['id'],
];

$ret = $dbc->fetchRow($sql, $arg);

if (empty($ret['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'sql' => $sql,
			'arg' => $arg,
		],
	], 404);
}

$ret['data'] = json_decode($ret['data'], true);

// Items
$ret['data']['item_list'] = [];
$res_item_list = $dbc->fetchAll('SELECT * FROM b2b_outgoing_item WHERE b2b_outgoing_id =:b0', [ ':b0' => $ret['id'] ]);
foreach ($res_item_list as $i) {
	$i['data'] = json_decode($i['data'], true);
	$ret['data']['item_list'][] = $i;
}

// File?
$ret['data']['file'] = $dbc->fetchRow('SELECT id, name FROM b2b_outgoing_file WHERE id = :b0', [ ':b0' => $ret['id'] ]);


return $RES->withJSON([
	'data' => $ret,
	'meta' => [],
], 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
