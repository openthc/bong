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
		$a->get('', 'OpenTHC\Bong\Controller\Vehicle\Create');

		// Create
		$a->post('', 'OpenTHC\Bong\Controller\Vehicle\Create');

		// Status
		$a->get('/status', 'OpenTHC\Bong\Controller\Vehicle\Status');

		// Single
		$a->get('/{id}', 'OpenTHC\Bong\Controller\Vehicle\Single');

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\Vehicle\Update');

		// Delete
		$a->delete('/{id}', 'OpenTHC\Bong\Controller\Vehicle\Delete');

	}
}
