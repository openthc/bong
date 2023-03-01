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
		$a->get('', '\OpenTHC\Bong\Controller\InventoryAdjust\Search');
		// function($REQ, $RES, $ARG) {
		// 	// $dbc = $REQ->getAttribute('dbc');
		// 	// $res = $dbc->fetchAll('SELECT id, hash, updated_at FROM lot ORDER BY updated_at DESC');
		// 	// return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		// 	return _from_cre_file('inventory-adjust/search.php', $REQ, $RES, $ARG);
		// });

		// Status
		$a->get('/status', '\OpenTHC\Bong\Controller\InventoryAdjust\Status');

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'inventory_adjust';
		$a->get('/{id}', $c);

		// $a->get('/{id}', function($REQ, $RES, $ARG) {
		// });

	}
}
