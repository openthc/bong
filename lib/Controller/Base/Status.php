<?php
/**
 * Base Status
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Status extends \OpenTHC\Controller\Base
{
	protected $_tab_name;

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		if (empty($this->_tab_name)) {
			__exit_text('Invalid Incantation [CBS-020]');
		}

		$dbc = _dbc();

		$arg = [];
		$sql_where = [];

		$sql = <<<SQL
		SELECT count(id) AS c, stat, data->'@result'->'data' AS e
		FROM %s
		{WHERE}
		GROUP BY 2, 3
		ORDER BY 2
		SQL;

		if ( ! empty($_SESSION['License']['id'])) {
			$sql_where[] = 'license_id = :l0';
			$arg[':l0'] = $_SESSION['License']['id'];
		}

		if ( ! empty($sql_where)) {
			$sql = str_replace('{WHERE}', sprintf('WHERE %s', implode(' AND ', $sql_where)), $sql);
		} else {
			$sql = str_replace('{WHERE}', '', $sql);
		}

		$sql = sprintf($sql, $this->_tab_name);
		$res = $dbc->fetchAll($sql, $arg);
		$out = object_status_tbody($this->_tab_name, $res);
		echo html_table_wrap(implode('', $out));

		exit(0);
	}

}
