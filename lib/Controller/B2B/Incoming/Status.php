<?php
/**
 * B2B Incoming Status Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\B2B\Incoming;

class Status extends \OpenTHC\Bong\Controller\Base\Status
{
	public $_tab_name = 'b2b_incoming';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = _dbc();

		// B2B Incoming Status
		$arg = [];
		$sql_where = [];

		$sql = <<<SQL
		SELECT count(b2b_incoming.id) AS c
			, b2b_incoming.stat
			, b2b_incoming.data->'@result'->'data' AS e
		FROM b2b_incoming
		{WHERE}
		GROUP BY 2, 3
		ORDER BY 2
		SQL;

		if ( ! empty($_GET['license'])) {
			$sql_where[] = 'target_license_id = :l0';
			$arg[':l0'] = $_GET['license'];
		} elseif ( ! empty($_SESSION['License']['id'])) {
			$sql_where[] = 'target_license_id = :l0';
			$arg[':l0'] = $_SESSION['License']['id'];
		}

		if ( ! empty($sql_where)) {
			$sql = str_replace('{WHERE}', sprintf('WHERE %s', implode(' AND ', $sql_where)), $sql);
		} else {
			$sql = str_replace('{WHERE}', '', $sql);
		}

		$sql = sprintf($sql, $this->_tab_name);
		$res = $dbc->fetchAll($sql, $arg);
		$out = $this->object_status_tbody('b2b/incoming', $res);
		echo $this->object_status_table($out);

		// B2B Incoming Item Status
		echo '<h4>B2B Incoming Item</h4>';

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

		if ( ! empty($_GET['license'])) {
			$sql_where[] = 'b2b_incoming.target_license_id = :l0';
			$arg[':l0'] = $_GET['license'];
		} elseif ( ! empty($_SESSION['License']['id'])) {
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
		$out = $this->object_status_tbody('b2b/incoming', $res);
		echo $this->object_status_table($out);

		exit(0);

	}
}
