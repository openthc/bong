<?php
/**
 * OpenTHC Pipe Application Bootstrap
 */

define('APP_ROOT', __DIR__);

error_reporting(E_ALL & ~ E_NOTICE);

openlog('openthc-bong', LOG_ODELAY|LOG_PID, LOG_LOCAL0);

require_once(APP_ROOT . '/vendor/autoload.php');


/**
 * Hands work Directly to View Script
 */
function _from_cre_file($f0, $RES, $ARG)
{
	$f0 = trim($f0, '/');
	$f1 = sprintf('%s/controller/%s/%s', APP_ROOT, $_SESSION['cre-base'], $f0);
	if (!is_file($f1)) {

		return $RES->withJSON([
			'data' => null,
			'meta' => [
				'cre' => $_SESSION['cre-base'],
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

};
