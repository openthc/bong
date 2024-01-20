<?php
/**
 * License Status
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\License;

class Status extends \OpenTHC\Bong\Controller\Base\Status
{
	protected $_tab_name = 'license';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		if ('full' == $_GET['v']) {
			return $this->_echo_status_full($REQ, $RES, $ARG);
		}

		$dbc = _dbc();

		$RET = [];

		$sql = 'select count(id) AS c, stat from license GROUP BY stat ORDER BY stat';
		$RET['license'] = $dbc->fetchAll($sql);

		echo '<table class="table table-sm">';
		echo '<thead class="table-dark"><tr><th>Status</th><th>Count</th></tr></thead>';
		echo '<tbody>';
		foreach ($RET['license'] as $idx => $rec) {

			printf('<tr><td>%d</td><td>%d</td></tr>'
				, $rec['stat']
				, $rec['c']
			);
		}
		echo '</tbody>';
		echo '</table>';

		exit(0);


	}

	/**
	 *
	 */
	function _echo_status_full($REQ, $RES, $ARG)
	{
		$dbc = _dbc();

		$sql = <<<SQL
		SELECT *
		FROM license
		WHERE stat NOT IN ('666', '403')
		ORDER BY code
		SQL;
		$res = $dbc->fetchAll($sql);

		$html = $this->render('license/status-full.php', [ 'license_list' => $res ]);

		$RES = $RES->write($html);

		return $RES;

	}
}
