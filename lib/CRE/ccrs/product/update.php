<?php
/**
 * Update a Product
 *
 * SPDX-License-Identifier: MIT
 *
 * For CCRS we just do an UPSERT every time
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT id, license_id FROM product WHERE id = :o1';
$arg = [ ':o1' => $ARG['id'] ];
$chk = $dbc->fetchRow($sql, $arg);
if ( ! empty($chk['id'])) {

	// License Conflict?
	if ($chk['license_id'] != $_SESSION['License']['id']) {
		return $RES->withJSON([
			'data' => null,
			'meta' => [
				'detail' => 'Access Denied'
			],
		], 409);
	}

}


// UPSERT IT
$sql = <<<SQL
INSERT INTO product (id, license_id, name, hash, data) VALUES (:o1, :l0, :n0, :h0, :d0)
ON CONFLICT (id) DO
UPDATE SET updated_at = now(), stat = 100, name = :n0, hash = :h0, data = product.data || :d0
WHERE product.id = :o1 AND product.license_id = :l0
SQL;

$arg = [
	':o1' => $ARG['id'],
	':l0' => $_SESSION['License']['id'],
	':n0' => $_POST['name'],
	':d0' => json_encode([
		'@version' => 'openthc/2015',
		'@source' => $_POST
	]),
];
$arg[':h0'] = sha1($arg[':d0']);

$ret = $dbc->query($sql, $arg);
if (1 == $ret) {
	return $RES->withJSON([
		'data' => [
			'id' => $ARG['id'],
			'name' => $_POST['name']
		],
		'meta' => $_POST,
	]);
}

return $RES->withJSON([
	'data' => null,
	'meta' => [
		'detail' => 'Invalid Object',
	],
], 500);
