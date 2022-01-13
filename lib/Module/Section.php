<?php
/**
 *
 *
 * SPDX-License-Identifier: GPL-3.0-only
 */

namespace OpenTHC\Bong\Module;

class Section extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {
			// return _from_cre_file('section/search.php', $RES, $ARG);
			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM section ORDER BY updated_at DESC');
			return $RES->withJSON($res);
		});

		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/create.php', $RES, $ARG);
		});

		$a->get('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/single.php', $RES, $ARG);
			// // Single
			// $c = new \OpenTHC\Bong\Controller\Single($this->_container);
			// $c->tab = 'section';
			// $a->get('/{id}', $c);
		});

		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/update.php', $RES, $ARG);
		});

		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/delete.php', $RES, $ARG);
		});

	}
}
