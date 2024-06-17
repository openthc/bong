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
		$a->post('', 'OpenTHC\Bong\Controller\Crop\Create');

		// Export
		$a->get('/export', 'OpenTHC\Bong\Controller\Crop\Export');

		// Status
		$a->get('/status', 'OpenTHC\Bong\Controller\Crop\Status');

		// Single
		$a->get('/{id}', 'OpenTHC\Bong\Controller\Crop\Single');

		// Update
		$a->post('/{id}', 'OpenTHC\Bong\Controller\Crop\Update');

		// Delete
		$a->delete('/{id}', 'OpenTHC\Bong\Controller\Crop\Delete');

		// Finish
		$a->post('/{id}/finish', 'OpenTHC\Bong\Controller\Crop\Finish');

		// Convenience Functions
		// $a->post('/{id}/move', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('crop/update.php', $REQ, $RES, $ARG);
		// });

		//$a->post('/{id}/collect', function($REQ, $RES, $ARG) {
		//	return _from_cre_file('crop/collect.php', $REQ, $RES, $ARG);
		//});

	}
}
