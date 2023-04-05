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

		// Status
		$a->get('/status','\OpenTHC\Bong\Controller\Crop\Status');

		// Single
		$a->get('/{id}','\OpenTHC\Bong\Controller\Crop\Single');

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return $RES->withJSON(array(
				'status' => 'failure',
				'detail' => 'Not Implemented [LMP#024]'
			), 501);
		});

		// Update
		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('crop/update.php', $REQ, $RES, $ARG);
		});

		// Delete
		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('crop/delete.php', $REQ, $RES, $ARG);
		});

		// Convenience Functions
		// $a->post('/{id}/move', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('crop/update.php', $REQ, $RES, $ARG);
		// });

		//$a->post('/{id}/collect', function($REQ, $RES, $ARG) {
		//	return _from_cre_file('crop/collect.php', $REQ, $RES, $ARG);
		//});

	}
}
