<?php
/**
 * Create Variety
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$rec = [
	'id' => _ulid(),
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

// $rec['hash'] = \OpenTHC\CRE\Base::recHash($rec);
$rec['hash'] = sha1(json_encode($rec));
$rec['data'] = json_encode($rec['data']);

// Set some flag so we know we need to sync it?
// Or Depend on updated_at?
$ret = $dbc->insert('variety', $rec);

return $RES->withJSON([
	'data' => $rec,
	'meta' => [],
], 201);
