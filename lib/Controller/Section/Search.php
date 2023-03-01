<?php
/**
 * Section Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Section;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	public $tab = 'section';
	protected $_tab_name = 'section';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		// return _from_cre_file('section/search.php', $RES, $ARG);

		$dbc = $REQ->getAttribute('dbc');

		if ( ! empty($_GET['e'])) {

			$sql = <<<SQL
			SELECT id, name, code, stat FROM license
			WHERE id IN (SELECT license_id FROM {$this->_tab_name} where data::text LIKE '%Integrator is not authorized%')
			ORDER BY id
			SQL;
			$res = $dbc->fetchAll($sql);
			if (count($res)) {
				__exit_text($res);
			}

		}

		// $res = $dbc->fetchAll("SELECT id, hash, updated_at, data->'result' AS result FROM section ORDER BY updated_at DESC");

		$sql = <<<SQL
		SELECT *
		FROM {$this->_tab_name}
		{WHERE}
		ORDER BY updated_at DESC
		SQL;

		// $sql = 'SELECT id, stat, hash, updated_at FROM section {WHERE} ORDER BY updated_at DESC';
		// $res = $dbc->fetchAll("SELECT id, hash, updated_at, data->'result' AS result FROM section ORDER BY updated_at DESC");
		// $sql = 'SELECT * FROM section {WHERE} ORDER BY updated_at DESC';
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
		// return $RES->withJSON($res);

		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':
				unset($res['sql']);
				return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			case 'text/html':
			default:

				$data = [];
				$data['object_list'] = $res;
				$data['column_list'] = [
					'id',
					// 'license_id',
					// 'license_id_target',
					'stat',
					'name',
					// 'data',
					// 'created_at',
					// 'updated_at',
				];
				$data['column_function'] = [
					'id' => function($val, $rec) { return sprintf('<td><a href="/lot/%s">%s</a></td>', $val, $val); },
					'name' => function($val, $rec) { return sprintf('<td>%s</td>', __h($val)); },
					// 'data' => function($val, $rec) {
					// 	// $val = json_decode($val, true);
					// 	// return sprintf('<td>%s</td>', json_encode($val['@result']), JSON_PRETTY_PRINT);
					// },
				];

				return $this->asHTML($data);

		}

	}

}
