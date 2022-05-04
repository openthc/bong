<?php
/**
 * Interface to Vehicle Data
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Vehicle extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {
			return _from_cre_file('vehicle/search.php', $RES, $ARG);
			// $dbc = $REQ->getAttribute('dbc');
			// $res = $dbc->fetchAll('SELECT id, hash, updated_at FROM vehicle ORDER BY updated_at DESC');
			// return $RES->withJSON($res);
		});

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'vehicle';
		$a->get('/{id}', $c);

	}
}
