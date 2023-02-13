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

		$sql = "SELECT count(id) AS c, stat, data->'@result'->'data' AS e FROM %s GROUP BY 2, 3 ORDER BY 2";

		$res = $dbc->fetchAll(sprintf($sql, $this->_tab_name));
		$out = object_status_tbody($this->_tab_name, $res);
		echo html_table_wrap(implode('', $out));

		exit(0);
	}

}
