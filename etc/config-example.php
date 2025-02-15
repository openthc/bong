<?php
/**
 * OpenTHC Bong Configuration Example
 */

// Init
$cfg = [];

// Base
$cfg = [
	'tz' => 'UTC',
];

// Database
$cfg['database'] = [
	'hostname' => '127.0.0.1',
	'username' => 'openthc_bong',
	'password' => 'openthc_bong',
	'database' => 'openthc_bong',
];

// OpenTHC
$cfg['openthc'] = [
	'app' => [
		'id' => '',
		'origin' => 'https://app.openthc.example.com',
		'secret' => '',
	],
	'dir' => [
		'origin' => 'https://dir.openthc.example.com',
	],
	'bong' => [
		'id' => '', // from openthc_auth.auth_service
		'origin' => 'https://bong.openthc.example.com',
		'secret' => '',
		'system' => [
			// IDS from openthc_auth database
			'company' => '',
			'contact' => '',
			'license' => '',
		]
	],
	'sso' => [
		'origin' => 'https://sso.openthc.example.com',
		'secret' => '',
	]
];

$cfg['openthc']['root'] = [
	'company' => [
		'id' => '018NY6XC00C0MPANY000000000',
	],
	'contact' => [
		'id' => '018NY6XC00C0NTACT000000000',
	],
	'license' => [
		'id' => '018NY6XC00L1CENSE000000000',
	]
];

return $cfg;
