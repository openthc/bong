<?php
/**
 * Inventory Adjust Routes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class InventoryAdjust extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Search
		$a->get('', 'OpenTHC\Bong\Controller\InventoryAdjust\Search');

		// Status
		$a->get('/status', 'OpenTHC\Bong\Controller\InventoryAdjust\Status');

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'inventory_adjust';
		$a->get('/{id}', $c);

	}
}
