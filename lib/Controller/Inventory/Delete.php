<?php
/**
 * Inventory Delete
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Inventory;

class Delete extends \OpenTHC\Controller\Base
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'inventory';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$dbc = $REQ->getAttribute('dbc');

		// Object Exists?
		$sql = 'SELECT id, license_id, stat FROM inventory WHERE id = :s0 AND license_id = :l0';
		$arg = [
			':l0' => $_SESSION['License']['id'],
			':s0' => $ARG['id']
		];
		$obj = $dbc->fetchRow($sql, $arg);
		if (empty($obj['id'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [
					'note' => 'Not Found'
				],
			], 404);
		}

		// OPA?

		// Current Status
		switch ($obj['stat']) {
		case 100:
		case 102:
		case 200:
		case 202:
		case 400:
			$sql = sprintf('UPDATE %s SET stat = 410 WHERE license_id = :l0 AND id = :o1', $this->_tab_name);
			$arg = [
				':l0' => $_SESSION['License']['id'],
				':o1' => $obj['id'],
			];
			$ret = $dbc->query($sql, $arg);
			$obj['stat'] = 410;
			return $RES->withJSON([
				'data' => $obj,
				'meta' => [
					'ret' => $ret,
				],
			], 200);
			break;
		case 410:
			// Already Deleted
			return $RES->withJSON([
				'data' => $obj,
				'meta' => [],
			], 410);
			break;
		default:
			return $RES->withJSON([
				'data' => $obj,
				'meta' => [
					'note' => 'Invalid Object Status [CID-063]',
				],
			], 500);
		}

		// Delete
		// $sql = sprintf('UPDATE %s SET stat = 410, deleted_at = now() WHERE license_id = :l0 AND id = :s0', $this->_tab_name);
		// $ret = $dbc->query($sql, $arg);
		// if (1 == $ret) {
		// 	return $RES->withJSON([
		// 		'data' => [
		// 			'stat' => 410,
		// 		],
		// 		'meta' => [],
		// 	]);
		// }

		return $RES->withJSON([
			'data' => null,
			'meta' => [
				'note' => 'Invalid Object',
			],
		], 500);

	}

}
