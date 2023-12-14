<?php
/**
 * Inventory Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Inventory;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	protected $_tab_name = 'inventory';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		$ret = [];
		$ret['data'] = $this->search($dbc);
		$ret['meta'] = [];

		// Content Type
		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':
				return $RES->withJSON($ret, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			case 'text/html':
			default:

				$data = $this->getDefaultColumns();
				$data['object_list'] = $ret['data'];
				$data['column_function']['id'] = function($val, $rec) { return sprintf('<td><a href="/inventory/%s">%s</a></td>', $val, $val); };

				$data['Page'] = [
					'title' => 'Inventory Search'
				];

				return $this->asHTML($data);

		}

	}

}
