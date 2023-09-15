<?php
/**
 * Authentication Middleware
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Middleware;

use OpenTHC\JWT;

class Auth
{
	/**
	 * Evaluate a JWT and/or other Authentication Headers
	 */
	function __invoke($REQ, $RES, $NMW)
	{
		// JWT
		$RES = $this->_check_jwt($RES);
		if (200 != $RES->getStatusCode()) {
			return $RES;
		}

		// @hack
		if ( ! empty($_GET['_'])) {
			if ('@hack' == $_GET['_']) {

				$_SESSION['Company'] = [
					'id' => $cfg['openthc']['root']['company']['id'],
				];

				$_SESSION['Contact'] = [
					'id' => $cfg['openthc']['root']['contact']['id'],
				];

				$_SESSION['License'] = [];

				$_SESSION['cre'] = [
					'id' => 'usa/wa/ccrs',
					'engine' => 'ccrs',
					'name' => '',
				];

				$_SESSION['cre-auth'] = [
					'company' => 'system',
					'license' => 'system',
					'username' => 'system',
					'password' => 'system',
				];
				$_SESSION['sql-name'] = 'openthc_bong_ccrs';
			}
		}

		// if (strlen($_SESSION['License']['id']) < 20) {
			// syslog(LOG_NOTICE, "Very Short License");
		// }

		$RES = $NMW($REQ, $RES);

		return $RES;

	}

	/**
	 * Evaluate the JWT Header
	 */
	function _check_jwt($RES)
	{
		$jwt = null;

		if ( ! empty($_SERVER['HTTP_OPENTHC_JWT'])) {
			$jwt = $_SERVER['HTTP_OPENTHC_JWT'];
		}

		if ( ! empty($_SERVER['HTTP_AUTHORIZATION'])) {
			if (preg_match('/^Bearer jwt:(.+)$/', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
				$jwt = $m[1];
			}
		}

		// Check JWT
		if ( ! empty($jwt)) {

			// @todo Lookup Key for Issuer
			// $rdb =
			// $jwt = JWT::decode_only($jwt);
			// $key = $rdb->get(sprintf('/%s/sk', $jwt['body']['iss']));
			// $chk = JWT::verify($jwt, $key);
			$chk = JWT::decode($jwt);

			// Temp Shit Hack
			$_SESSION['cre'] = [
				'id' => 'usa/wa/ccrs',
				'engine' => 'ccrs',
				'name' => '',
			];

			// Temp Shit Hack
			$_SESSION['cre-auth'] = [
				'company' => '',
				'license' => '',
				'username' => '',
				'password' => '',
			];

			// Temp Shit Hack
			$_SESSION['sql-name'] = 'openthc_bong_ccrs';

			// Mostly Real Now
			$_SESSION['Contact'] = [
				'id' => $chk['sub'],
			];
			$_SESSION['Company'] = [
				'id' => $chk['company'],
			];
			$_SESSION['License'] = [
				'id' => $chk['license'],
			];

			if (empty($_SESSION['Company']['id'])) {
				return $RES->withJSON(['meta' => [ 'note' => 'Invalid Company [LMA-113]' ]], 400);
			}
			if (empty($_SESSION['Contact']['id'])) {
				return $RES->withJSON(['meta' => [ 'note' => 'Invalid Contact [LMA-116]' ]], 400);
			}
			if (empty($_SESSION['License']['id'])) {
				return $RES->withJSON(['meta' => [ 'note' => 'Invalid License [LMA-119]' ]], 400);
			}

		}

		return $RES;

	}

}
