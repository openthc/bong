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
		$jwt = null;

		if ( ! empty($_GET['jwt'])) {
			$jwt = $_GET['jwt'];
		}
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
				'username' => 'bullshit',
				'password' => 'bullshit',
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
				return $RES->withJSON(['meta' => [ 'detail' => 'Invalid Company' ]], 400);
			}
			if (empty($_SESSION['Contact']['id'])) {
				return $RES->withJSON(['meta' => [ 'detail' => 'Invalid Contact' ]], 400);
			}
			if (empty($_SESSION['License']['id'])) {
				return $RES->withJSON(['meta' => [ 'detail' => 'Invalid License' ]], 400);
			}

		}

		$RES = $NMW($REQ, $RES);

		return $RES;

	}

}
