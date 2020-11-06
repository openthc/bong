<?php
/**
 *
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

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'product';
		$a->get('/{id}', $c);

	}
}
