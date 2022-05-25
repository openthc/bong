<?php
/**
 * B2C Retail Sales
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class B2C extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM b2c_outgoing ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

		// $a->get('', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('retail/search.php', $RES, $ARG);
		// });

		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('retail/create.php', $RES, $ARG);
		});

		$a->get('/{id}', function($REQ, $RES, $ARG) {
			return $RES->withJSON(array('status' => 'failure', 'detail' => 'Not Implemented'), 500);
		});

		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return $RES->withJSON(array('status' => 'failure', 'detail' => 'Not Implemented'), 500);
		});

		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return $RES->withJSON(array('status' => 'failure', 'detail' => 'Not Implemented'), 500);
		});


	}
}
