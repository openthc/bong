<?php
/**
 * UPSERT a Crop Record
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT id, license_id, data FROM crop WHERE id = :s0';
$arg = [ ':s0' => $ARG['id'] ];
$chk = $dbc->fetchRow($sql, $arg);
if ( ! empty($chk['id'])) {

	// License Conflict
	if ($chk['license_id'] != $_SESSION['License']['id']) {
		return $RES->withJSON([
			'data' => null,
			'meta' => [
				'detail' => 'Access Denied'
			],
		], 409);
	}

}


// UPSERT
$sql = <<<SQL
INSERT INTO crop (id, license_id, name, hash, data) VALUES (:o1, :l0, :n0, :h0, :d0)
ON CONFLICT (id) DO
UPDATE SET updated_at = now(), stat = 100, name = :n0, hash = :h0, data = coalesce(crop.data, '{}'::jsonb) || :d0
WHERE crop.id = :o1 AND crop.license_id = :l0
SQL;

$arg = [
	':o1' => $ARG['id'],
	':l0' => $_SESSION['License']['id'],
	':n0' => $_POST['name'] ?: $ARG['id'],
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
			'id' => $arg[':o1'],
			'name' => $arg[':n0'],
			'hash' => $arg[':h0'],
		],
		'meta' => [],
	]);
}

return $RES->withJSON([
	'data' => null,
	'meta' => [
		'detail' => 'Invalid Object'
	],
], 500);
