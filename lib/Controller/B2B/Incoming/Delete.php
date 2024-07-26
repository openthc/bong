<?php
/**
 * B2B Incoming Delete Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\B2B\Incoming;

class Delete extends \OpenTHC\Controller\Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		return $RES->withJSON([
			'data' => null,
			'meta' => [ 'note' => 'Not Implemented' ]
		], 501);
	}

}
