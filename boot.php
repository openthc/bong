<?php
/**
 * OpenTHC Pipe Application Bootstrap
 */

define('APP_ROOT', __DIR__);

error_reporting(E_ALL & ~ E_NOTICE);

openlog('openthc-bong', LOG_ODELAY|LOG_PID, LOG_LOCAL0);

require_once(APP_ROOT . '/vendor/autoload.php');

if ( ! \OpenTHC\Config::init(APP_ROOT) ) {
	_exit_html_fail('<h1>Invalid Application Configuration [ALB-035]</h1>', 500);
}

/**
 * Hands work Directly to View Script
 */
function _from_cre_file($f0, $RES, $ARG)
{
	$f0 = trim($f0, '/');
	$f1 = sprintf('%s/controller/%s/%s', APP_ROOT, $_SESSION['cre']['engine'], $f0);
	if (!is_file($f1)) {

		return $RES->withJSON([
			'data' => null,
			'meta' => [
				'cre' => $_SESSION['cre']['engine'],
				'detail' => 'Interface not implemented [APP#046]',
			]
		], 501, JSON_PRETTY_PRINT);

		// return $RES->withJSON(array(
		// 	'status' => 'failure',
		// 	'detail' => 'Not Found',
		// 	'_f' => $f,
		// 	'_s' => $_SESSION,
		// 	'_R' => $_SERVER,
		// ), 404);
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
