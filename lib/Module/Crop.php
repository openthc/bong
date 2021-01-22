<?php
/**
 *
 */

namespace OpenTHC\Bong\Module;

class Crop extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		// Search
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM crop ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'crop';
		$a->get('/{id}', $c);

		// Search
		// $a->get('', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('plant/search.php', $RES, $ARG);
		// });

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return $RES->withJSON(array(
				'status' => 'failure',
				'detail' => 'Not Implemented [LMP#024]'
			), 501);
		});

		// Single
		// $a->get('/{id}', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('plant/single.php', $RES, $ARG);
		// });

		// Update
		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('plant/update.php', $RES, $ARG);
		});

		// Convenience Functions
		$a->post('/{id}/move', function($REQ, $RES, $ARG) {
			return _from_cre_file('plant/update.php', $RES, $ARG);
		});

		//$a->post('/{id}/collect', function($REQ, $RES, $ARG) {
		//	return _from_cre_file('crop/collect.php', $RES, $ARG);
		//});

	}
}
