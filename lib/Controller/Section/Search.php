<?php
/**
 * Section Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Section;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	protected $_tab_name = 'section';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		$res = $this->search($dbc);

		// Content Type
		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':
				unset($res['sql']);
				return $RES->withJSON([
					'data' => $res,
					'meta' => [],
				], 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			case 'text/html':
			default:

				$data = $this->getDefaultColumns();
				$data['object_list'] = $ret['data'];
				$data['column_function']['id'] = function($val, $rec) { return sprintf('<td><a href="/section/%s">%s</a></td>', $val, $val); };

				return $this->asHTML($data);

		}

	}

}
