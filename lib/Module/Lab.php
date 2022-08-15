<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Lab extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM lab_result ORDER BY updated_at DESC');

			return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		});

		// $a->get('', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('lab/search.php', $RES, $ARG);
		// });

		$a->get('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('lab/single.php', $RES, $ARG);
		});

	}
}
