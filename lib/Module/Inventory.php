<?php
/**
 * Inventory Routes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Inventory extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Search
		$a->get('', '\OpenTHC\Bong\Controller\Inventory\Search');

		// Create
		$a->post('', '\OpenTHC\Bong\Controller\Inventory\Create');

		// Status
		$a->get('/status', '\OpenTHC\Bong\Controller\Inventory\Status');

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'lot';
		$a->get('/{id}', $c);
		// $a->get('/{id}', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('lot/single.php', $REQ, $RES, $ARG);
		// });

		// Update
		$a->post('/{id}', '\OpenTHC\Bong\Controller\Inventory\Update');

		// Delete Item
		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('lot/delete.php', $REQ, $RES, $ARG);
		});

		// Adjust
		$a->post('/{id}/adjust', 'OpenTHC\Bong\Controller\Inventory\Adjust');

		//	$a->post('/', function($REQ, $RES, $ARG) {
		//		die('Create Inventory');
		//	});
		//
		//	// Combine Inventory to a new Type
		//	$a->post('/combine', function($REQ, $RES, $ARG) {
		//		return $RES->withJson(array(
		//			'ulid' => ULID::generate(), // '1234567890123456',
		//			'weight' => 123.45,
		//			'weight_unit' => 'g',
		//			'quantity' => 1,
		//		));
		//	});
		//
		//	// Convert Inventory to a new Type
		//	$a->post('/convert', function($REQ, $RES, $ARG) {
		//		return $RES->withJson(array(
		//			'code' => '123456',
		//			'weight' => '',
		//			'weight_unit' => 123.45,
		//			'quantity' => 1,
		//		));
		//	})->add(function($req, $RES) {
		//		// Enfore Type => Type Rules
		//		//die(print_r($_POST));
		//	});
		//

	}
}
