<?php
/**
 *
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Auth extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		$a->map(['GET','POST'], '/open', 'OpenTHC\Bong\Controller\Auth\Open');
		$a->map(['GET','POST'], '/ping', 'OpenTHC\Bong\Controller\Auth\Ping');
		// $a->map(['GET','POST'], '/ping', 'OpenTHC\Controller\Auth\Ping');
		$a->get('/shut', 'OpenTHC\Controller\Auth\Shut');

	}

}
