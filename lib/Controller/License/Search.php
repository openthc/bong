<?php
/**
 * License Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\License;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	protected $_tab_name = 'license';

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
				$data['h1'] = 'License :: Search';
				$data['object_list'] = $ret['data'];
				$data['column_list'] = [ 'id', 'code', 'name', 'stat' ];
				$data['column_function']['id'] = function($val, $rec) { return sprintf('<td><a href="/license/%s">%s</a></td>', $val, $val); };

				return $this->asHTML($data);

		}

	}

	/**
	 *
	 */
	function search($dbc)
	{
		$sql = <<<SQL
		SELECT *
		FROM {$this->_tab_name}
		{WHERE}
		ORDER BY id
		SQL;

		$sql_param = [];
		$sql_where = [];

		if ( ! empty($_GET['q'])) {
			$sql_where[] = 'data::text LIKE :q23';
			$sql_param[':q23'] = sprintf('%%%s%%', $_GET['q']);
		}

		if ( ! empty($_GET['stat'])) {
			$sql_where[] = 'stat = :q52';
			$sql_param[':q52'] = $_GET['stat'];
		}

		if (count($sql_where)) {
			$sql_where = implode(' AND ', $sql_where);
			$sql = str_replace('{WHERE}', sprintf(' WHERE %s', $sql_where), $sql);
		} else {
			$sql = str_replace('{WHERE}', '', $sql);
		}

		$res = $dbc->fetchAll($sql, $sql_param);

		return $res;

	}

}
