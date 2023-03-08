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
		$a->get('', 'OpenTHC\Bong\Controller\License\Search');

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('license/create.php', $REQ, $RES, $ARG);
		});

		// Status
		$a->get('/status', 'OpenTHC\Bong\Controller\License\Status');

		// License Type
		$a->get('/type', 'OpenTHC\Bong\Controller\System:license_type');

		// Single
		$a->get('/{id}', 'OpenTHC\Bong\Controller\License\Single');

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\License\Update');

		// Verify
		$a->post('/{id}/verify', 'OpenTHC\Bong\Controller\License\Verify');

	}
}
