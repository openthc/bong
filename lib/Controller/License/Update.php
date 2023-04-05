<?php
/**
 * License Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\License;

class Update extends \OpenTHC\Bong\Controller\Base\Update
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

		if ($ARG['id'] != $_SESSION['License']['id']) {
			return $RES->withJSON([], 403, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		$ret = [];
		$ret['data'] = null;
		$ret['meta'] = [];

		$ret_code = 200;

		$dbc = $REQ->getAttribute('dbc');
		$res = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
		if (empty($res['id'])) {
			$ret['meta']['note'] = 'License Not Found';
			$ret_code = 404;
		} else {
			$res = $dbc->query('UPDATE license SET code = :c0, name = :n0, company_id = :company_id WHERE id = :l0', [
				':company_id' => $_POST['company_id'],
				':l0' => $_SESSION['License']['id'],
				':c0' => $_POST['code'],
				':n0' => $_POST['name'],
			]);
			$ret['data'] = $res;
		}

		return $RES->withJSON($ret, $ret_code, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}
}
