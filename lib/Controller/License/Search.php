<?php
/**
 * License Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\License;

class Search extends \OpenTHC\Bong\Controller\Base\Search
{
	protected $_tab_name = 'license';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');
		$res = $dbc->fetchAll('SELECT id, name, hash, updated_at FROM license ORDER BY updated_at DESC');

		return $RES->withJSON($res, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}

}
