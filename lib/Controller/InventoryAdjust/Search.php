<?php
/**
 * InventoryAdjust Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\InventoryAdjust;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	public $tab = 'inventory_adjust';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		$sql = "SELECT * FROM {$this->tab} {WHERE} ORDER BY updated_at DESC";

		$sql_param = [];
		$sql_where = [];

		// Add License & Paging

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

		$res = [];
		// $res['sql'] = $sql;
		$res['data'] = $dbc->fetchAll($sql, $sql_param);

		// Content Type
		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		// $want_type = 'application/json';
		switch ($want_type) {
			case 'application/json':
				return $RES->withJSON($res['data'], 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			case 'text/html':
			default:
				$x = new \OpenTHC\Controller\Base($this->_container);
				$data = [];
				$data['object_list'] = $res['data'];
				$data['column_list'] = [
					'id',
					'inventory_id',
					'name',
					'stat'
					// 'data',
					// 'created_at',
					// 'updated_at',
				];
				$data['column_function'] = [
					'id' => function($val, $rec) { return sprintf('<td><a href="/inventory-adjust/%s">%s</a></td>', $val, $val); },
					'inventory_id' => function($val, $rec) { return sprintf('<td><a href="/inventory/%s">%s</a></td>', $val, $val); },
					// 'name' => function($val, $rec) { return sprintf('<td>%s</td>', __h($val)); },
					// 'data' => function($val, $rec) {
					// 	// $val = json_decode($val, true);
					// 	// return sprintf('<td>%s</td>', json_encode($val['@result']), JSON_PRETTY_PRINT);
					// },
				];

				return $x->render('search.php', $data);


		}



	}
}
