<?php
/**
 * Section Routes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Section extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Search
		$a->get('', 'OpenTHC\Bong\Controller\Section\Search');

		// Status
		$a->get('/status','\OpenTHC\Bong\Controller\Section\Status');

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/create.php', $REQ, $RES, $ARG);
		});

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'section';
		$a->get('/{id}', $c);

		// $a->get('/{id}', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('section/single.php', $RES, $ARG);
		// });

		// Update
		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/update.php', $REQ, $RES, $ARG);
		});

		// Delete
		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/delete.php', $REQ, $RES, $ARG);
		});

	}
}
