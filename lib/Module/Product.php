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

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'product';
		$a->get('/{id}', $c);

		// $a->get('/{id}', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('product/single.php', $RES, $ARG);
		// });

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\Product\Update');

		// Delete
		$a->delete('/{id}', 'OpenTHC\Bong\Controller\Product\Delete');

	}
}
