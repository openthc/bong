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
