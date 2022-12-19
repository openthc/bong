<?php
/**
 * Vehicle Routes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Vehicle extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Search
		$a->get('', function($REQ, $RES, $ARG) {
			return _from_cre_file('vehicle/search.php', $REQ, $RES, $ARG);
			// $dbc = $REQ->getAttribute('dbc');
			// $res = $dbc->fetchAll('SELECT id, hash, updated_at FROM vehicle ORDER BY updated_at DESC');
			// return $RES->withJSON($res);
		});

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('vehicle/create.php', $REQ, $RES, $ARG);
		});

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'vehicle';
		$a->get('/{id}', $c);

		// Update
		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('vehicle/update.php', $REQ, $RES, $ARG);
		});

		// Delete
		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('vehicle/delete.php', $REQ, $RES, $ARG);
		});

	}
}
