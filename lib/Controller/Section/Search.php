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
		$dbc = $REQ->getAttribute('dbc');

		$res = $this->search($dbc);

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
					'license_id',
					// 'license_id_target',
					'stat',
					'name',
					// 'data',
					// 'created_at',
					// 'updated_at',
				];
				$data['column_function'] = [
					'id' => function($val, $rec) { return sprintf('<td><a href="/lot/%s">%s</a></td>', $val, $val); },
					'license_id' => function($val, $rec) { return sprintf('<td><a href="/license/%s">%s</a></td>', $val, $val); },
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
