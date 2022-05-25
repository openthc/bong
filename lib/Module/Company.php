<?php
/**
 *
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Company extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM company ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

	}
}
