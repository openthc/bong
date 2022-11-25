<?php
/**
 * Create Variety
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$rec = [
	// CCRS uses Name as Primary Key
	// Has limit of 50 characters
	'id' => strtoupper(substr(trim($_POST['name']), 0, 50)),
	'license_id' => '018NY6XC00L1CENSE000000000',
	'name' => trim($_POST['name']),
	'data' => [
		'@source' => [
			'Strain' => $_POST['name'],
			'StrainType' => $_POST['type'] ?: 'Hybrid',
			'CreatedBy' => $_SESSION['Contact']['name'] ?: '-system-',
			'CreatedDate' => date('m/d/Y')
		]
	]
];

// Already have this one?
$chk = $dbc->fetchOne('SELECT id FROM variety WHERE id = :s0', [
	':s0' => $rec['id']
]);

if ( ! empty($chk)) {

	return $RES->withJSON([
		'data' => [
			'id' => $chk,
		],
		'meta' => [],
	], 200);

}

// $rec['hash'] = \OpenTHC\CRE\Base::recHash($rec);
$rec['hash'] = sha1(json_encode($rec));
$rec['data'] = json_encode($rec['data']);

$ret = $dbc->insert('variety', $rec);

return $RES->withJSON([
	'data' => $rec,
	'meta' => [],
], 201);
