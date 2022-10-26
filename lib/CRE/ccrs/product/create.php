<?php
/**
 * Create a Product
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$rec = [
	'license_id' => $_SERVER['HTTP_OPENTHC_LICENSE'],
	'name' => $_POST['name'],
	'guid' => $_POST['guid'],
	'meta' => [
		'@source' => $_POST,
	]
];

$ret = $dbc->insert('product', $rec);

return $RES->withJSON([
	'data' => $rec,
	'meta' => [],
], 201);
