<?php
/**
 * Crop Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Crop;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	protected $_tab_name = 'crop';

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

				$data = $this->getDefaultColumns();
				$data['object_list'] = $ret['data'];
				$data['column_function']['id'] = function($val, $rec) { return sprintf('<td><a href="/crop/%s">%s</a></td>', $val, $val); };

				return $this->render('search.php', $data);


		}

	}

}
