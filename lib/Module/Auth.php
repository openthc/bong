<?php
/**
 * Authentication Stuffs
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
		$a->map(['GET', 'POST'], '/open', 'OpenTHC\Bong\Controller\Auth\Open');

		$a->get('/ping', 'OpenTHC\Bong\Controller\Auth\Ping')
			->add('OpenTHC\Bong\Middleware\Database')
			->add('OpenTHC\Bong\Middleware\CRE')
			;

		$a->get('/shut', 'OpenTHC\Controller\Auth\Shut');

	}

}
