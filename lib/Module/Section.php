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

		// Create
		$a->post('', 'OpenTHC\Bong\Controller\Section\Create');

		// Status
		$a->get('/status', 'OpenTHC\Bong\Controller\Section\Status');

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'section';
		$a->get('/{id}', $c);

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\Section\Update');

		// Delete
		$a->delete('/{id}', 'OpenTHC\Bong\Controller\Section\Delete');

	}
}
