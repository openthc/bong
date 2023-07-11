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
		$a->get('', 'OpenTHC\Bong\Controller\Variety\Search');

		// Create
		$a->post('', 'OpenTHC\Bong\Controller\Variety\Create');

		// Status
		$a->get('/status','OpenTHC\Bong\Controller\Variety\Status');

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'variety';
		$a->get('/{id}', $c);

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\Variety\Update');

		// Delete
		$a->delete('/{id}', 'OpenTHC\Bong\Controller\Variety\Delete');

	}
}
