<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Variety extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		// Search
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, stat, hash, updated_at FROM variety ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'variety';
		$a->get('/{id}', $c);

	}
}
