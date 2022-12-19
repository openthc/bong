<?php
/**
 * Product Related Modules
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
		$a->get('', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/search.php', $REQ, $RES, $ARG);
		});

		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/create.php', $REQ, $RES, $ARG);
		});

		// $a->get('/{id}', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('product/single.php', $RES, $ARG);
		// });

		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'product';
		$a->get('/{id}', $c);


		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/update.php', $REQ, $RES, $ARG);
		});

		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('product/delete.php', $REQ, $RES, $ARG);
		});

	}
}
