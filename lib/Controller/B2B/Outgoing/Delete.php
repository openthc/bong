<?php
/**
 * B2B Outgoing Delete Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\B2B\Outgoing;

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
