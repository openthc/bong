<?php
/**
 * Create Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Create extends \OpenTHC\Controller\Base
{
	use \OpenTHC\Bong\Traits\GetReturnObject;
	use \OpenTHC\Bong\Traits\UpdateStatus;

	protected $_tab_name;

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		if (empty($this->_tab_name)) {
			__exit_text('Invalid Incantation [CBS-020]', 500);
		}

	}

	function checkObjectExists($RES, $dbc, $oid)
	{
		$sql = <<<SQL
		SELECT id, license_id, name, hash, stat, data
		FROM {$this->_tab_name}
		WHERE license_id = :l0 AND id = :s0
		SQL;

		$arg = [
			':l0' => $_SESSION['License']['id'],
			':s0' => $oid,
		];

		$chk = $dbc->fetchRow($sql, $arg);

		if ( ! empty($chk['id'])) {
			// $ret_code = 409;
			// $chk['stat'] = $ret_code;
			return $RES->withJSON([
				'data' => $chk,
				'meta' => [ 'note' => 'Object Exists [CBC-048]' ],
			], 409);

		}

		return $RES;

	}

}
