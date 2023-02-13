<?php
/**
 * Section Routes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Section extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Search
		$a->get('', function($REQ, $RES, $ARG) {

			// return _from_cre_file('section/search.php', $RES, $ARG);
			$dbc = $REQ->getAttribute('dbc');

			// $res = $dbc->fetchAll("SELECT id, hash, updated_at, data->'result' AS result FROM section ORDER BY updated_at DESC");
			$sql = 'SELECT * FROM section {WHERE} ORDER BY updated_at DESC';
			// $sql = 'SELECT id, stat, hash, updated_at FROM section {WHERE} ORDER BY updated_at DESC';

			$sql_param = [];
			$sql_where = [];

			// $sql_where[] = 'license_id = :l0';
			// $sql_param[':l0'] = $_SESSION['License']['id'];

			if ( ! empty($_GET['q'])) {
				$sql_where[] = 'data::text LIKE :q23';
				$sql_param[':q23'] = sprintf('%%%s%%', $_GET['q']);
			}

			if (count($sql_where)) {
				$sql_where = implode(' AND ', $sql_where);
				$sql = str_replace('{WHERE}', sprintf(' WHERE %s', $sql_where), $sql);
			} else {
				$sql = str_replace('{WHERE}', '', $sql);
			}

			$res = $dbc->fetchAll($sql, $sql_param);
			return $RES->withJSON($res);

		});

		// Status
		$a->get('/status','\OpenTHC\Bong\Controller\Section\Status');

		// Create
		$a->post('', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/create.php', $REQ, $RES, $ARG);
		});

		// Single
		$c = new \OpenTHC\Bong\Controller\Single($this->_container);
		$c->tab = 'section';
		$a->get('/{id}', $c);

		// $a->get('/{id}', function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('section/single.php', $RES, $ARG);
		// });

		// Update
		$a->post('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/update.php', $REQ, $RES, $ARG);
		});

		// Delete
		$a->delete('/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('section/delete.php', $REQ, $RES, $ARG);
		});

	}
}
