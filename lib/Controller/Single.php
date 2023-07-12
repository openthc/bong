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

	function __invoke($REQ, $RES, $ARG)
	{
		$dbc = $REQ->getAttribute('dbc');
		$sql = sprintf('SELECT id, hash, stat, created_at, updated_at, data FROM %s WHERE id = :pk', $this->_tab_name);
		$rec = $dbc->fetchRow($sql, [
			':pk' => $ARG['id']
		]);

		if (empty($rec['id'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [],
			], 404);
		}

		return $RES->withJSON([
			'data' => json_decode($rec['data'], true),
			'meta' => [
				'stat' => $rec['stat'],
				'hash' => $rec['hash'],
				'created_at' => $rec['created_at'],
				'updated_at' => $rec['updated_at'],
			]
		]);

	}

}
