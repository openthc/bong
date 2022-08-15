<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class License extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, name, hash, updated_at FROM license ORDER BY updated_at DESC');

			return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		});

		$a->get('/{id}', function($REQ, $RES, $ARG) {
			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
			return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		});

	}
}
