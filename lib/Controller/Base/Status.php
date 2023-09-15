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

		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':

				return $RES->withJSON([
					'data' => $res,
					'meta' => [],
				], 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			default:

				// echo date('Y-m-d H:i:s');
				$tbody = $this->object_status_tbody($this->_tab_name, $res);
				echo $this->object_status_table($tbody);

		}

		return $RES;
	}

	/**
	 * Create Object Status Table
	 */
	function object_status_table($html) : string
	{
		if (empty($html)) {
			return '<strong>No Data</strong>';
		}

		ob_start();
		echo '<table class="table table-sm table-bordered table-hover">';
		echo '<thead class="table-dark">';
		echo '<tr><th style="width: 8em;">Status</th><th style="width: 8em;">Count</th><th>Errors</th></tr></thead>';
		echo '<tbody>';
		echo $html;
		echo '</tbody>';
		echo '</table>';

		return ob_get_clean();

	}

	/**
	 * Output Helper
	 */
	function object_status_tbody($obj, $res) : string
	{
		if (empty($res)) {
			return null;
		}

		$ret = [];
		foreach ($res as $rec) {
			$ret[] = sprintf('<tr><td><a href="/%s?stat=%d">%d</a></td><td class="r">%d</td><td><a href="/%s?q=%s">%s</a></td></tr>'
				, $obj
				, $rec['stat']
				, $rec['stat']
				, $rec['c']
				, $obj
				, rawurlencode($rec['e'])
				, __h($rec['e'])
			);
		}

		return implode('', $ret);

	}

}
