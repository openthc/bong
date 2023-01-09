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

$_POST['name'] = trim($_POST['name']);
if (empty($_POST['name'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [ 'detail' => 'Invalid Name [CSU-030]' ]
	], 400);
}

// UPSERT Section
$sql = <<<SQL
INSERT INTO section (id, license_id, name, hash, data)
VALUES (:o0, :l0, :n0, :h0, :d0)
ON CONFLICT (id) DO
UPDATE SET updated_at = now(), stat = 100, name = :n0, hash = :h0, data = coalesce(section.data, '{}'::jsonb) || :d0
SQL;
$arg = [
	':o0' => $ARG['id'],
	':l0' => $_SESSION['License']['id'],
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
