<?php
/**
 * Common Controller for Single Object
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller;

use OpenTHC\Bong\CRE;

class Single extends \OpenTHC\Controller\Base
{
	protected $_tab_name = null;

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$dbc = $REQ->getAttribute('dbc');

		$arg = [];

		$filter = [];
		if ( ! empty($_SESSION['License']['id'])) {
			$filter[] = 'license_id = :l0';
			$arg[':l0'] = $_SESSION['License']['id'];
		} elseif ( ! empty($_GET['license_id'])) {
			$filter[] = 'license_id = :l0';
			$arg[':l0'] = $_SESSION['License']['id'];
		}
		// Added License context /mbw 2023-139
		// $license_id = null;
		// if ( ! empty($_SESSION['License']['id'])) {
		// 	$license_id = $_SESSION['License']['id'];
		// }
		// if ( ! empty($_GET['license_id'])) {
		// 	$license_id = $_GET['license_id'];
		// }
		// if ( ! empty($license_id)) {
		// 	$sql.= ' AND license_id = :l0';
		// 	$arg[':l0'] = $license_id;
		// }


		$filter[] = ' id = :pk';
		$arg[':pk'] = $ARG['id'];

		$sql = sprintf('SELECT * FROM %s', $this->_tab_name);
		$sql.= ' WHERE ';
		$sql.= implode(' AND ', $filter);

		$rec = $dbc->fetchRow($sql, $arg);

		if (empty($rec['id'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Not Found [LCS-046]' ],
			], 404);
		}

		$rec['data'] = json_decode($rec['data'], true);

		$ret = [
			'data' => $rec,
			'meta' => [
				'stat' => $rec['stat'],
				'hash' => $rec['hash'],
				'created_at' => $rec['created_at'],
				'updated_at' => $rec['updated_at'],
			]
		];

		return $RES->withJSON($ret, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}

}
