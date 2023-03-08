<?php
/**
 * Product Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Product;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	public $tab = 'crop';
	public $_tab_name = 'crop';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		$ret = [];
		$ret['data'] = $this->search($dbc);
		$ret['meta'] = [];

		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':
				return $RES->withJSON($ret, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			case 'text/html':
			default:

				$data = [];
				$data['object_list'] = $ret['data'];
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
					'id' => function($val, $rec) { return sprintf('<td><a href="/crop/%s">%s</a></td>', $val, $val); },
					'license_id' => function($val, $rec) { return sprintf('<td><a href="/license/%s">%s</a></td>', $val, $val); },
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
