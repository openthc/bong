<?php
/**
 * Load the CRE
 */

namespace OpenTHC\Bong\Middleware;

class CRE extends \OpenTHC\Middleware\Base
{

	public function __invoke($REQ, $RES, $NMW)
	{

		if (empty($_SESSION['cre'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'detail' => 'Invalid Authentication State [BMC-017]' ],
			], 403);
		}

		if (empty($_SESSION['cre-auth'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'detail' => 'Invalid Authentication State [BMC-024]' ],
			], 403);
		}

		$RES = $NMW($REQ, $RES);

		return $RES;
	}

}
