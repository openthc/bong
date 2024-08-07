<?php
/**
 * B2B Outgoing Search Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\B2B\Outgoing;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	public $tab = 'product';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		$tab = 'b2b_outgoing';

		// Search the Table
		$sql = <<<SQL
		SELECT *
		FROM $tab
		{WHERE}
		ORDER BY updated_at DESC
		OFFSET 0
		LIMIT 250
		SQL;

		$sql_param = [];
		$sql_where = [];

		$sql_where[] = 'source_license_id = :l0';
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
		$res['data'] = $dbc->fetchAll($sql, $sql_param);
		$res['meta'] = [];
		$res['meta']['sql'] = $sql;

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
					'created_at',
					'updated_at',
					'stat',
					'source_license_id',
					'target_license_id',
					'name',
					'data',
				];
				$data['column_function'] = [
					'id' => function($val, $rec) { return sprintf('<td><a href="/b2b/outgoing/%s">%s</a></td>', $val, $val); },
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
