<?php
/**
 * OpenTHC Docker Bong Service Init
 */

_init_config();

// Bootstrap OpenTHC Service
$d = dirname(__DIR__);
require_once("$d/boot.php");

// Wait for Database
$dsn = getenv('OPENTHC_DSN_BONG');
$dbc_main = _spin_wait_for_sql($dsn);
// echo "SQL Connection: MAIN\n";



function _init_config()
{
	// CONFIG
	$cfg = [
		'tz' => 'America/Los_Angeles',
	];

	$cfg['database'] = [
		'hostname' => 'sql',
		'username' => 'openthc_bong',
		'password' => 'openthc_bong',
		'database' => 'openthc_bong',
	];

	// Redis
	$cfg['redis'] = [
		'hostname' => 'rdb',
	];

	// CRE Details
	$cfg['cre'] = [
		'usa' => [
			'wa' => [
				'ccrs' => [
					'tz' => 'America/Los_Angeles',
					'username' => '',
					'password' => '',
					'server' => 'https://cannabisreporting.lcb.wa.gov/',
					'service-key' => ''
				],
			]
		]
	];

	// Used?
	// $cfg['openthc']['cre'] = [
	// 	'id' => $cfg_application_id,
	// 	'secret' => $cfg_application_sk,
	// 	'hostname' => 'bong',
	// ];

	//
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

}



/**
 *
 */
function _spin_wait_for_sql(string $dsn)
{

	$try = 0;

	do {

		$try++;

		try {

			$ret = new \Edoceo\Radix\DB\SQL($dsn);

			return $ret;

		} catch (Exception $e) {
			// Ignore
			echo "SQL Failure: ";
			echo $e->getMessage();
			// echo "\n";
			// var_dump($e);
		}

		sleep(4);

	} while ($try < 16);

	throw new \Exception('Failed to connect to database');

	exit(1);
}
