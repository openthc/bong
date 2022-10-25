<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Lot extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		// Search
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM lot ORDER BY updated_at DESC');

			return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		});

		// $a->get('', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('lot/search.php', $REQ, $RES, $ARG);
		// });

		// $a->get('/history', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('lot/history/search.php', $REQ, $RES, $ARG);
		// });

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'lot';
		$a->get('/{id}', $c);

		// View Item
		// $a->get('/{guid}', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('lot/single.php', $REQ, $RES, $ARG);
		// });

		//	// Update Item
		//	$a->post('/{guid:[0-9a-f]+}', function($REQ, $RES, $ARG) {
		//		die('Update Inventory Item');
		//	});

		// Delete Item
		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('lot/delete.php', $REQ, $RES, $ARG);
		});


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
