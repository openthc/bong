<?php
/**
 * Update a Product
 *
 * SPDX-License-Identifier: MIT
 *
 * For CCRS we just do an UPSERT every time
 */

$_POST['name'] = trim($_POST['name']);
$_POST['type'] = trim($_POST['type']);

// Name Check
if (empty($_POST['name'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'note' => 'Invalid Product Name [CPU-033]'
		],
	], 400);
}

// Type
if (empty($_POST['type'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'note' => 'Invalid Product Type [CPU-044]'
		],
	], 400);
}

$dbc = $REQ->getAttribute('dbc');

// UPSERT IT
$sql = <<<SQL
INSERT INTO product (id, license_id, name, hash, data) VALUES (:o1, :l0, :n0, :h0, :d0)
ON CONFLICT (id, license_id) DO
UPDATE SET updated_at = now(), stat = 100, name = :n0, hash = :h0, data = coalesce(product.data, '{}'::jsonb) || :d0
WHERE product.hash != :h0
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
$arg[':h0'] = \OpenTHC\CRE\Base::objHash( [
	'id' => $ARG['id'],
	'name' => $_POST['name'],
	'type' => $_POST['type'],
	'package' => $_POST['package'],
]);


$ret = $dbc->query($sql, $arg);
if (1 == $ret) {

	// $job_ulid = _ulid();

	$R = \OpenTHC\Service\Redis::factory();
	// $R->set(sprintf('/job/%s', $job_ulid), json_encode([
	// 	'license' => $_SESSION['License']['id'],
	// 	'object' => 'product',
	// 	'object-id' => $ARG['id']
	// ]));
	// $R->rpush('/cre/ccrs/upload-queue', $job_ulid);
	$R->set(sprintf('/license/%s/product', $_SESSION['License']['id']), 100);

}

return $RES->withJSON([
	'data' => [
		'id' => $ARG['id'],
		'name' => $_POST['name']
	],
	'meta' => [],
]);
