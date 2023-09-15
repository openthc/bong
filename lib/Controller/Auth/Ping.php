<?php
/**
 * Ping Local Connection
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Auth;

use OpenTHC\Bong\CRE;

class Ping extends \OpenTHC\Controller\Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$code = 200;
		$flag = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		$data = [
			'data' => session_id(),
			'meta' => $_SESSION,
		];

		if (empty($_SESSION['cre'])) {
			$code = 403;
			$data['data'] = null;
			$data['meta']['detail'] = 'Invalid Session State';
		} elseif (empty($_SESSION['cre-auth'])) {
			$code = 403;
			$data['data'] = null;
			$data['meta']['detail'] = 'Invalid Session State';
		}

		// $_SESSION['sql-conn'] = null;
		// $_SESSION['sql-name'] = null;

		$dbc = _dbc();

		// Company Check
		if ( ! empty($_SESSION['company']['id'])) {
			$chk = $dbc->fetchRow('SELECT * FROM company WHERE id = :c0', [
				':c0' => $_SESSION['company']['id']
			]);
		}

		// License Check
		if ( ! empty($_SESSION['license']['id'])) {
			$chk = $dbc->fetchRow('SELECT * FROM license WHERE id = :c0', [
				':c0' => $_SESSION['license']['id']
			]);
			if ($chk['stat'] != 200) {
				$code = $chk['stat'];
			}
		}

		return $RES->withJSON($data, $code, $flag);

	}

}
