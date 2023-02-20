<?php
/**
 * Delete a Variety
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Variety;

class Delete extends \OpenTHC\Controller\Base
{
	protected $_tab_name = 'variety';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		$sql = 'SELECT * FROM variety WHERE name = :v0';
		$arg = [
			':v0' => $ARG['id'],
		];

		$res = $dbc->fetchRow($sql, $arg);

		if (empty($res['id'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [],
			], 404);
		}

		// $V = new \OpenTHC\Variety($dbc, $res);
		// $V->setFlag(\OpenTHC\Variety::FLAG_MUTE);
		// $V->save('Variety/Delete');

		return $RES->withJSON([
			'data' => $V->toArray(),
			'meta' => [],
		]);

	}

}
