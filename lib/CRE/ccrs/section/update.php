<?php
/**
 * UPSERT a Section
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT id, license_id FROM section WHERE id = :s0';
$arg = [ ':s0' => $ARG['id'] ];
$chk = $dbc->fetchRow($sql, $arg);
if ( ! empty($chk['id'])) {

	if ($chk['license_id'] != $_SESSION['License']['id']) {
		return $RES->withJSON([
			'data' => null,
			'meta' => [
				'detail' => 'Access Conflict [CSU-019]'
			],
		], 409);
	}

}


// UPSERT Section
$sql = <<<SQL
INSERT INTO section (id, license_id, name, created_at, updated_at, hash, data)
VALUES (:o0, :l0, :n0, :ct0, :ut0, :h0, :d0)
ON CONFLICT (id) DO
UPDATE SET created_at = :ct0, updated_at = :ut0, stat = 100, name = :n0, hash = :h0, data = coalesce(section.data, '{}'::jsonb) || :d0
SQL;
// $sql = 'UPDATE section SET name = :n0, updated_at = now() WHERE license_id = :l0 AND id = :s0';
$arg = [
	':o0' => $ARG['id'],
	':l0' => $_SESSION['License']['id'],
	':ct0' => $_POST['created_at'],
	':ut0' => $_POST['updated_at'],
	':n0' => $_POST['name'],
	':h0' => '-',
	':d0' => json_encode([
		'@version' => 'openthc/2015',
		'@source' => $_POST,
	]),
];
$arg[':h0'] = sha1($arg['d0']);

$ret = $dbc->query($sql, $arg);
if (1 == $ret) {
	return $RES->withJSON([
		'data' => [
			'id' => $ARG['id'],
			'name' => $_POST['name']
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
