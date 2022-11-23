<?php
/**
 * License Create
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$rec = [
	'id' => $_POST['id'],
	'company_id' => $_POST['company_id'],
	'code' => $_POST['code'],
	'name' => $_POST['name'],
];

$res = $dbc->insert('license', $rec);

return $RES->withJSON([
	'data' => $rec,
	'meta' => [],
], 201);
