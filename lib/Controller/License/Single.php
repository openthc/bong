<?php
/**
 * License Single View
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\License;

class Single extends \OpenTHC\Controller\Base
{
	protected $_tab_name = 'license';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		if ('current' == $ARG['id']) {
			$ARG['id'] = $_SESSION['License']['id'];
		}

		$dbc = $REQ->getAttribute('dbc');

		$ret = [];
		$ret['data'] = [];
		$ret['meta'] = [];

		$ret_code = 200;

		$res = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
		if (empty($res['id'])) {
			$ret['meta']['note'] = 'License Not Found';
			$ret_code = 404;
		} else {

			$ret['data'] = $res;

			if ( ! empty($_GET['object-status'])) {

				// Get Stats?
				$arg = [ ':l0' => $ARG['id'] ];
				$stat = [];
				$stat['section'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM section WHERE license_id = :l0 GROUP BY stat ORDER BY stat', $arg);
				$stat['variety'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM variety WHERE license_id = :l0 GROUP BY stat ORDER BY stat', $arg);
				$stat['product'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM product WHERE license_id = :l0 GROUP BY stat ORDER BY stat', $arg);

				$stat['crop'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM crop WHERE license_id = :l0 GROUP BY stat ORDER BY stat', $arg);
				$stat['inventory'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM inventory WHERE license_id = :l0 GROUP BY stat ORDER BY stat', $arg);
				// $stat['inventory_adjust'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM inventory_adjust WHERE license_id = :l0 GROUP BY stat ORDER BY stat', $arg);

				$stat['b2b-incoming'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM b2b_incoming WHERE target_license_id = :l0 GROUP BY stat ORDER BY stat', $arg);
				$stat['b2b-outgoing'] = $dbc->fetchAll('SELECT count(id) AS c, stat FROM b2b_outgoing WHERE source_license_id = :l0 GROUP BY stat ORDER BY stat', $arg);

				$ret['data']['object-status'] = $stat;

			}

		}

		$ret['data']['redis-key'] = sprintf('/license/%s', $ARG['id']);

		$rdb = \OpenTHC\Service\Redis::factory();
		$d0 = $rdb->hgetall($ret['data']['redis-key']);
		ksort($d0);
		$ret['data']['redis-cache'] = $d0;

		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':
				return $RES->withJSON($ret, $ret_code, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			case 'text/html':
			default:

				$html = $this->render('license/single.php', $ret['data']);

				$RES = $RES->write($html);

				return $RES;
				// return $RES->withStatus($ret_code);

		}

	}
}
