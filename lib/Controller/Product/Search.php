<?php
/**
 * Product Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Product;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	public $tab = 'product';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		// if ($_GET['e'])
		if (isset($_GET['e'])) {

			$sql = <<<SQL
			SELECT id, name, code, stat FROM license
			WHERE id IN (SELECT license_id FROM product where data::text LIKE '%Integrator is not authorized%')
			ORDER BY id
			SQL;

			$res = $dbc->fetchAll($sql);

			if (count($res)) {
				__exit_text($res);
			}

		}

		$sql = <<<SQL
		SELECT *
		FROM product
		{WHERE}
		ORDER BY updated_at DESC
		OFFSET 0
		LIMIT 500
		SQL;

		$sql_param = [];
		$sql_where = [];

		$sql_where[] = 'license_id = :l0';
		$sql_param[':l0'] = $_SESSION['License']['id'];

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

		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':
				return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			case 'text/html':
			default:

				$data = [];
				$data['object_list'] = $res['data'];
				$data['column_list'] = [
					'id',
					'name',
					'stat',
					// 'created_at',
					// 'updated_at',
					// 'data',
				// 	// 'license_id',
				// 	// 'license_id_target',
				];
				$data['column_function'] = [
					'id' => function($val, $rec) { return sprintf('<td><a href="/product/%s">%s</a></td>', $val, $val); },
					'name' => function($val, $rec) { return sprintf('<td>%s</td>', __h($val)); },
					'data' => function($val, $rec) {
						$val = json_decode($val, true);
						// return sprintf('<td>%s</td>', json_encode($val['@result']), JSON_PRETTY_PRINT);
						return sprintf('<td>%s</td>', implode(', ', array_keys($val)));
					},
				];

				return $this->render('search.php', $data);


		}

	}

}
