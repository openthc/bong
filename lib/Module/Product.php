<?php
/**
 * Product Routes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Product extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Search
		$a->get('', 'OpenTHC\Bong\Controller\Product\Search');

		// Create
		$a->post('', 'OpenTHC\Bong\Controller\Product\Create');

		// Status
		$a->get('/status', 'OpenTHC\Bong\Controller\Product\Status');

		// Product Type
		$a->get('/type', 'OpenTHC\Bong\Controller\System:product_type');

		// Single
		$a->get('/{id}', 'OpenTHC\Bong\Controller\Product\Single');

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\Product\Update');

		// Delete
		$a->delete('/{id}', 'OpenTHC\Bong\Controller\Product\Delete');

	}
}
