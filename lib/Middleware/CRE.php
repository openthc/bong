<?php
/**
 * Load the CRE
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Middleware;

class CRE extends \OpenTHC\Middleware\Base
{
	/**
	 *
	 */
	public function __invoke($REQ, $RES, $NMW)
	{
		if (empty($_SESSION['cre'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Authentication State [BMC-017]' ],
			], 403);
		}

		if (empty($_SESSION['cre-auth'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Authentication State [BMC-024]' ],
			], 403);
		}

		$RES = $NMW($REQ, $RES);

		return $RES;
	}

}
