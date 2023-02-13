<?php
/**
 * Variety Routes
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

			// return _from_cre_file('variety/search.php', $REQ, $RES, $ARG);

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, stat, hash, updated_at FROM variety ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

		// Status
		$a->get('/status','\OpenTHC\Bong\Controller\Variety\Status');

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('variety/create.php', $REQ, $RES, $ARG);
		});

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'variety';
		$a->get('/{id}', $c);

		// Update
		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('variety/update.php', $REQ, $RES, $ARG);
		});

		// Delete
		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('variety/delete.php', $REQ, $RES, $ARG);
		});

	}
}
