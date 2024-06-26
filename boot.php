<?php
/**
 * OpenTHC BONG Application Bootstrap
 *
 * SPDX-License-Identifier: MIT
 */

use \Edoceo\Radix\DB\SQL;

define('APP_ROOT', __DIR__);

error_reporting(E_ALL & ~ E_NOTICE & ~ E_WARNING);

openlog('openthc-bong', LOG_ODELAY|LOG_PID, LOG_LOCAL0);

require_once(APP_ROOT . '/vendor/autoload.php');

if ( ! \OpenTHC\Config::init(APP_ROOT) ) {
	_exit_html_fail('<h1>Invalid Application Configuration [ALB-035]</h1>', 500);
}

define('OPENTHC_SERVICE_ID', \OpenTHC\Config::get('openthc/bong/id'));
define('OPENTHC_SERVICE_ORIGIN', \OpenTHC\Config::get('openthc/bong/origin'));


/**
 * Database Connection
 */
function _dbc()
{
	static $ret;

	if (empty($ret)) {

		$cfg = \OpenTHC\Config::get('database');
		$dsn = sprintf('pgsql:host=%s;dbname=%s;application_name=openthc-bong', $cfg['hostname'], $cfg['database']);
		if (getenv('PGBOUNCER_PORT')) {
			$dsn = sprintf('pgsql:port=6543;dbname=%s;application_name=openthc-bong-pool', $cfg['database']);
		}
		$ret = new \Edoceo\Radix\DB\SQL($dsn, $cfg['username'], $cfg['password']);

	}

	return $ret;

}

/**
 * Hands work Directly to View Script
 */
function _from_cre_file($f0, $REQ, $RES, $ARG)
{
	$f0 = trim($f0, '/');
	$f1 = sprintf('%s/lib/CRE/%s/%s', APP_ROOT, $_SESSION['cre']['engine'], $f0);
	if ( ! is_file($f1)) {

		return $RES->withJSON([
			'data' => [
				'f0' => $f0,
				'f1' => $f1,
				'cre' => $_SESSION['cre']['engine'],
			],
			'meta' => [
				'note' => 'Interface not implemented [OBB-056]',
			]
		], 501);

	}

	$out = require_once($f1);

	return $out;

}


/**
 * Set Option
 */
function _set_option($dbc, $key, $val)
{
	if ($dbc->fetchOne("SELECT id FROM base_option WHERE key = :k", [ ':k' => $key ])) {
		$dbc->update('base_option', [ 'val' => $val ], [ 'key' => $key ]);
	} else {
		$dbc->insert('base_option', [
			'id' => _ulid(),
			'key' => $key,
			'val' => $val,
		]);
	}
}
