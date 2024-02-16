<?php
/**
 * Company
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Company;

class Single extends \OpenTHC\Bong\Controller\Single
{
	protected $_tab_name = 'company';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$dbc = $REQ->getAttribute('dbc');

		if ('current' == $ARG['id']) {
			$ARG['id'] = $_SESSION['Company']['id'];
		}

		$ret = [];
		$ret['data'] = [];
		$ret['meta'] = [];

		$ret_code = 200;

		$res = $dbc->fetchRow('SELECT * FROM company WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
		if (empty($res['id'])) {
			$ret['meta']['note'] = 'Company Not Found';
			$ret_code = 404;
		} else {
			$ret['data'] = $res;
		}

		$want_type = strtolower(trim(strtok($_SERVER['HTTP_ACCEPT'], ';')));
		switch ($want_type) {
			case 'application/json':
				$RES = $RES->withJSON($ret, $ret_code, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'text/html':
			default:

				$html = $this->render('company/single.php', $ret['data']);

				$RES = $RES->write($html);
				$RES = $RES->withStatus($ret_code);

		}

		return $RES;

	}

}
