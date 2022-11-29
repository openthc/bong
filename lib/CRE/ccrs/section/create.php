<?php
/**
 * Create a Section
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$rec = [
	'id' => $_POST['id'],
	'license_id' => $_SESSION['License']['id'],
	'name' => $_POST['name'],
	'data' => json_encode([
		'@source' => $_POST
	])
];

$ret = $dbc->insert('section', $rec);

return $RES->withJSON([
	'data' => $rec,
	'meta' => [],
], 201);
