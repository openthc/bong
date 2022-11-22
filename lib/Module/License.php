<?php
/**
 * License Data Module
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class License extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, name, hash, updated_at FROM license ORDER BY updated_at DESC');

			return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		});

		// Single
		$a->get('/{id}', function($REQ, $RES, $ARG) {

			if ('current' == $ARG['id']) {
				$ARG['id'] = $_SESSION['License']['id'];
			}
			// return _from_cre_file('license/single.php', $REQ, $RES, $ARG);

			$ret = [];
			$ret['data'] = null;
			$ret['meta'] = [];

			$ret_code = 200;

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
			if (empty($res['id'])) {
				$ret['meta']['detail'] = 'License Not Found';
				$ret_code = 404;
			} else {
				$ret['data'] = $res;
			}

			return $RES->withJSON($ret, $ret_code, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		});

	}
}
