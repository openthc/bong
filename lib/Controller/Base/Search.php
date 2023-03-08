<?php
/**
 * Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Search extends \OpenTHC\Controller\Base
{
	protected $_tab_name;

	/**
	 * Common Search Routine
	 */
	function search($dbc)
	{
		$sql = <<<SQL
		SELECT *
		FROM {$this->_tab_name}
		{WHERE}
		ORDER BY updated_at DESC
		OFFSET %d
		LIMIT 500
		SQL;

		$off = intval($_GET['offset']);
		$sql = sprintf($sql, $off);

		// $sql = 'SELECT id, stat, hash, updated_at FROM section {WHERE} ORDER BY updated_at DESC';
		// $res = $dbc->fetchAll("SELECT id, hash, updated_at, data->'result' AS result FROM section ORDER BY updated_at DESC");
		// $sql = 'SELECT * FROM section {WHERE} ORDER BY updated_at DESC';
		// $sql = 'SELECT id, stat, hash, updated_at FROM section {WHERE} ORDER BY updated_at DESC';

		$sql_param = [];
		$sql_where = [];

		if ($_SESSION['License']['id']) {
			$sql_where[] = 'license_id = :l0';
			$sql_param[':l0'] = $_SESSION['License']['id'];
		}

		if ( ! empty($_GET['q'])) {
			$sql_where[] = 'data::text LIKE :q23';
			$sql_param[':q23'] = sprintf('%%%s%%', $_GET['q']);
		}

		if ( ! empty($_GET['stat'])) {
			$sql_where[] = 'stat = :q52';
			$sql_param[':q52'] = $_GET['stat'];
		}

		if (count($sql_where)) {
			$sql_where = implode(' AND ', $sql_where);
			$sql = str_replace('{WHERE}', sprintf(' WHERE %s', $sql_where), $sql);
		} else {
			$sql = str_replace('{WHERE}', '', $sql);
		}

		$res = $dbc->fetchAll($sql, $sql_param);

		return $res;

	}

	/**
	 * Default Columns
	 */
	function getDefaultColumns()
	{
		$data = [];
		$data['object_list'] = [];
		$data['column_list'] = [
			'id',
			'license_id',
			'name',
			'stat',
			// 'created_at',
			// 'updated_at',
			// 'data',
		];
		$data['column_function'] = [
			// 'id' => function($val, $rec) { return sprintf('<td><a href="/crop/%s">%s</a></td>', $val, $val); },
			'license_id' => function($val, $rec) { return sprintf('<td><a href="/license/%s">%s</a></td>', $val, $val); },
			'name' => function($val, $rec) { return sprintf('<td>%s</td>', __h($val)); },
			'data' => function($val, $rec) {
				$val = json_decode($val, true);
				// return sprintf('<td>%s</td>', json_encode($val['@result']), JSON_PRETTY_PRINT);
				return sprintf('<td>%s</td>', implode(', ', array_keys($val)));
			},
		];

		return $data;

	}

	/**
	 *
	 */
	function showErrors()
	{
		if (isset($_GET['e'])) {

			var_dump($_GET);

			exit;

			$sql = <<<SQL
			SELECT id, name, code, stat FROM license
			WHERE id IN (SELECT license_id FROM {$this->_tab_name} where data::text LIKE '%Integrator is not authorized%')
			ORDER BY id
			SQL;

			$res = $dbc->fetchAll($sql);

			if (count($res)) {
				__exit_text($res);
			}

			////

			if ( ! empty($_GET['e'])) {

				$sql = <<<SQL
				SELECT id, name, code, stat FROM license
				WHERE id IN (SELECT license_id FROM lot where data::text LIKE '%Invalid Area%')
				ORDER BY id
				SQL;
				$res = $dbc->fetchAll($sql);
				if (count($res)) {
						__exit_text($res);
				}

			}

		}

	}

	/**
	 *
	 */
	function showStatus()
	{
		if (isset($_GET['s'])) {
			// Show All Records w/Stat
		}
	}

	/**
	 *
	 */
	function asHTML($res_data)
	{
		// $subC = new \OpenTHC\Controller\Base($this->_container);
		// $data = [];
		// $data['object_list'] = $res_data;
		// $data['column_list'] = [
		// 	'id',
		// 	'inventory_id',
		// 	'name',
		// 	'stat'
		// 	// 'data',
		// 	// 'created_at',
		// 	// 'updated_at',
		// ];
		// $data['column_function'] = [
		// 	'id' => function($val, $rec) { return sprintf('<td><a href="/inventory-adjust/%s">%s</a></td>', $val, $val); },
		// 	'inventory_id' => function($val, $rec) { return sprintf('<td><a href="/inventory/%s">%s</a></td>', $val, $val); },
		// 	// 'name' => function($val, $rec) { return sprintf('<td>%s</td>', __h($val)); },
		// 	// 'data' => function($val, $rec) {
		// 	// 	// $val = json_decode($val, true);
		// 	// 	// return sprintf('<td>%s</td>', json_encode($val['@result']), JSON_PRETTY_PRINT);
		// 	// },
		// ];

		return $this->render('search.php', $res_data);

	}

	/**
	 *
	 */
	function asJSON()
	{

	}

}
