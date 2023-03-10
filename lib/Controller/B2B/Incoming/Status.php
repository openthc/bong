<?php
/**
 * B2B Incoming Status Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\B2B\Incoming;

class Status extends \OpenTHC\Bong\Controller\Base\Status
{
	public $_tab_name = 'b2b_incoming_item';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = _dbc();

		$arg = [];
		$sql_where = [];

		$sql = <<<SQL
		SELECT count(b2b_incoming_item.id) AS c, b2b_incoming_item.stat, b2b_incoming_item.data->'@result'->'data' AS e
		FROM b2b_incoming
		JOIN b2b_incoming_item ON b2b_incoming.id = b2b_incoming_item.b2b_incoming_id
		{WHERE}
		GROUP BY 2, 3
		ORDER BY 2
		SQL;

		if ( ! empty($_SESSION['License']['id'])) {
			$sql_where[] = 'b2b_incoming.target_license_id = :l0';
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
