<?php
/**
 * License Data Module
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class License extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Search
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, name, hash, updated_at FROM license ORDER BY updated_at DESC');

			return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		});

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('license/create.php', $REQ, $RES, $ARG);
		});

		// License Type
		$a->get('/type', 'OpenTHC\Bong\Controller\System:license_type');

		// Single
		$a->get('/{id}', function($REQ, $RES, $ARG) {

			if ('current' == $ARG['id']) {
				$ARG['id'] = $_SESSION['License']['id'];
			}

			return _from_cre_file('license/single.php', $REQ, $RES, $ARG);

		});

	}
}
