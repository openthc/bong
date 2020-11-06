<?php
/**
 * B2C Retail Sales
 */

namespace OpenTHC\Bong\Module;

class B2C extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM b2c_sale ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

	}
}
