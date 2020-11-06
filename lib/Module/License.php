<?php
/**
 *
 */

namespace OpenTHC\Bong\Module;

class License extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM license ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

	}
}
