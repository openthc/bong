<?php
/**
 * Variety Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Variety;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	protected $_tab_name = 'variety';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

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
				$data['column_function']['id'] = function($val, $rec) { return sprintf('<td><a href="/variety/%s">%s</a></td>', $val, $val); };

				return $this->asHTML($data);

		}

	}

}
