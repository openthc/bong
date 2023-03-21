<?php
/**
 * Bong Example Config
 */

// Our Prefered Timezone
$cfg = [
	'tz' => 'UTC',
];

// These are BONG Service Keys for DEV
$cfg['application'] = [
	// This service ID from openthc_auth.auth_service
	'id' => '',
	// This service hash from openthc_auth.auth_service
	'secret' => '',
];

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

$cfg['openthc'] = [];
$cfg['openthc']['app'] = [
	'id' => '',
	'secret' => '',
	'base' => 'https://app.openthc.example.com/'
];
$cfg['openthc']['bong'] = [
	'id' => '', // from openthc_auth.auth_service
	'secret' => '',
	'base' => 'https://bong.openthc.example.com/',
	'system' => [
		// IDS from openthc_auth database
		'company' => '',
		'contact' => '',
		'license' => '',
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
