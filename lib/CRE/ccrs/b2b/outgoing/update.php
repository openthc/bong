<?php
/**
 * UPSERT B2B Outgoing Records
 *
 * SPDX-License-Identifier: MIT
 */

$have = $want = 0;

$dbc = $REQ->getAttribute('dbc');

$sql = 'SELECT id, target_license_id FROM b2b_outgoing WHERE id = :s0';
$arg = [ ':s0' => $ARG['id'] ];
$chk = $dbc->fetchRow($sql, $arg);
if ( ! empty($chk['id'])) {

	if ($chk['target_license_id'] != $_SESSION['License']['id']) {
		return $RES->withJSON([
			'data' => $ARG['id'],
			'meta' => [
				'detail' => 'Access Denied [BIU-026]'
			],
		], 409);
	}

}

// UPSERT B2B Outgoing
$sql = <<<SQL
INSERT INTO b2b_outgoing (id, source_license_id, target_license_id, created_at, updated_at, name, hash, data)
VALUES (:o1, :sl0, :tl0, :ct0, :ut0, :n0, :h0, :d0)
ON CONFLICT (id) DO
UPDATE SET created_at = :ct0, updated_at = :ut0, stat = 100, name = :n0, hash = :h0, data = coalesce(b2b_outgoing.data, '{}'::jsonb) || :d0
WHERE b2b_outgoing.id = :o1 AND b2b_outgoing.target_license_id = :tl0
SQL;

$arg = [
	':o1' => $ARG['id'],
	':sl0' => $_POST['source']['id'],
	':tl0' => $_SESSION['License']['id'],
	':ct0' => $_POST['created_at'],
	':ut0' => $_POST['updated_at'],
	':n0' => $_POST['name'],
	':d0' => json_encode([
		'@version' => 'openthc/2015',
		'@source' => $_POST
	]),
];
$arg[':h0'] = sha1($arg[':d0']);

$want++;
$ret = $dbc->query($sql, $arg);
if (1 == $ret) {
	$have++;
	// return $RES->withJSON([
	// 	'data' => [
	// 		'id' => $ARG['id'],
	// 		'name' => $_POST['name']
	// 	],
	// 	'meta' => $_POST,
	// ]);
}

$b2b_ret = [];
$b2b_ret['id'] = $arg[':o1'];
$b2b_ret['item_list'] = [];

// UPSERT B2B Outgoing Item
foreach ($_POST['item_list'] as $b2b_item) {

	$sql = <<<SQL
	INSERT INTO b2b_outgoing_item (id, b2b_outgoing_id, name, hash, data) VALUES (:o1, :b2b1, :n0, :h0, :d0)
	ON CONFLICT (id) DO
	UPDATE SET updated_at = now(), stat = 100, name = :n0, hash = :h0, data = coalesce(b2b_outgoing_item.data, '{}'::jsonb) || :d0
	WHERE b2b_outgoing_item.id = :o1 AND b2b_outgoing_item.b2b_outgoing_id = :b2b1
	SQL;

	$arg = [
		':o1' => $b2b_item['id'],
		':b2b1' => $b2b_ret['id'],
		':n0' => $b2b_item['id'],
		':d0' => json_encode([
			'@version' => 'openthc/2015',
			'@source' => $b2b_item
		]),
	];
	$arg[':h0'] = sha1($arg[':d0']);

	$want++;
	$ret = $dbc->query($sql, $arg);
	if (1 == $ret) {
		$have++;
		$b2b_ret['item_list'][] = [
			'id' => $arg[':o1'],
			'stat' => 200,
		];
		// return $RES->withJSON([
		// 	'data' => [
		// 		'id' => $ARG['id'],
		// 		'name' => $_POST['name']
		// 	],
		// 	'meta' => $_POST,
		// ]);
	} else {
		$b2b_ret['item_list'][] = [
			'id' => $arg[':o1'],
			'stat' => 500,
		];
	}

}

if ($want > 0) {

	if ($have == $want) {
		return $RES->withJSON([
			'data' => $b2b_ret,
			'meta' => null, // $_POST,
		]);
	}

	return $RES->withJSON([
		'data' => $b2b_ret,
		'meta' => null, // $_POST,
	]);

}


return $RES->withJSON([
	'data' => null,
	'meta' => [
		'detail' => 'Not Implemented',
	],
], 501);
