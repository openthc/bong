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

// CRE Details
$cfg['cre'] = [
	'usa' => [
		'wa' => [
			'ccrs' => [
				'tz' => 'America/Los_Angeles',
				// SAW Account Username & Password
				'username' => '',
				'password' => '',
				'server' => 'https://cannabisreporting.lcb.wa.gov/',
				// License Number or Service Key from the LCB
				'service-key' => ''
			],
		]
	]
];

// OpenTHC
$cfg['openthc'] = [
	'app' => [
		'id' => '',
		'origin' => 'https://app.openthc.example.com/',
		'secret' => '',
	],
	'bong' => [
		'id' => '', // from openthc_auth.auth_service
		'origin' => 'https://bong.openthc.example.com/',
		'secret' => '',
		'system' => [
			// IDS from openthc_auth database
			'company' => '',
			'contact' => '',
			'license' => '',
		]
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
