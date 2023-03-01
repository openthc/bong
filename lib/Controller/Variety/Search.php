<?php
/**
 * Variety Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Variety;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	public $tab = 'variety';
	protected $_tab_name = 'variety';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$sql = <<<SQL
		SELECT id, name, stat, hash, created_at, updated_at
		FROM variety
		WHERE license_id = :l0
		ORDER BY updated_at DESC
		OFFSET 0
		LIMIT 500
		SQL;

		$arg = [];
		$arg[':l0'] = $_SESSION['License']['id'];

		// Search
		// $sql = <<<SQL
		// SELECT *
		// FROM variety
		// WHERE name = :v0 OR name LIKE :v1
		// ORDER BY name
		// LIMIT 25
		// SQL;
		// $arg = [];
		// $arg[':v0'] = $q;
		// $arg[':v1'] = sprintf('%%%s%%', $arg[':v0']);

		$dbc = $REQ->getAttribute('dbc');

		$res = [];
		// $res['sql'] = $sql;
		$res['data'] = $dbc->fetchAll($sql, $arg);


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
					// 'hash',
				];
				$data['column_function'] = [
					'id' => function($val, $rec) { return sprintf('<td><a href="/variety/%s">%s</a></td>', $val, $val); },
					'name' => function($val, $rec) { return sprintf('<td>%s</td>', __h($val)); },
					'data' => function($val, $rec) {
						$val = json_decode($val, true);
						// return sprintf('<td>%s</td>', json_encode($val['@result']), JSON_PRETTY_PRINT);
						return sprintf('<td>%s</td>', implode(', ', array_keys($val)));
					},
				];

				return $this->asHTML($data);

		}


	}

}



// $q = trim($_GET['q']);
// if (empty($q)) {

// 	return $RES->withJSON([
// 		'data' => null,
// 		'meta' => [
// 			'detail' => 'Invalid Request; Parameter "q" must be provided" [CVS-014]'
// 		],
// 	], 400);

// }
