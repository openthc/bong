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
		if ( ! empty($_SERVER['HTTP_OPENTHC_JWT'])) {

			$chk = JWT::decode($_SERVER['HTTP_OPENTHC_JWT']);

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
			$_SESSION['contact'] = [
				'id' => $chk['sub'],
			];
			$_SESSION['company'] = [
				'id' => $chk['company'],
			];
			$_SESSION['license'] = [
				'id' => $chk['license'],
			];

			if (empty($_SESSION['company']['id'])) {
				return $RES->withStatus(400);
			}
			if (empty($_SESSION['contact']['id'])) {
				return $RES->withStatus(400);
			}
			if (empty($_SESSION['license']['id'])) {
				return $RES->withStatus(400);
			}

		}

		$RES = $NMW($REQ, $RES);

		return $RES;

	}

}
