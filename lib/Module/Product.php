<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Product extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		// Search
		$a->get('', function($REQ, $RES, $ARG) {
			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM product ORDER BY updated_at DESC');
			return $RES->withJSON($res);
		});

		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/create.php', $RES, $ARG);
		});

		$a->get('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/single.php', $RES, $ARG);
		});

		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/update.php', $RES, $ARG);
		});

		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/delete.php', $RES, $ARG);
		});

	}
}
