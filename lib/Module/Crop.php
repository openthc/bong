<?php
/**
 * Crop Interfaces
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Crop extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		// Search
		$a->get('', 'OpenTHC\Bong\Controller\Crop\Search');

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return $RES->withJSON(array(
				'status' => 'failure',
				'detail' => 'Not Implemented [LMP#024]'
			), 501);
		});

		// Status
		$a->get('/status', 'OpenTHC\Bong\Controller\Crop\Status');

		// Single
		$a->get('/{id}', 'OpenTHC\Bong\Controller\Crop\Single');

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\Crop\Update');

		// Delete
		$a->delete('/{id}', 'OpenTHC\Bong\Controller\Crop\Delete');

		// Convenience Functions
		// $a->post('/{id}/move', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('crop/update.php', $REQ, $RES, $ARG);
		// });

		//$a->post('/{id}/collect', function($REQ, $RES, $ARG) {
		//	return _from_cre_file('crop/collect.php', $REQ, $RES, $ARG);
		//});

	}
}
