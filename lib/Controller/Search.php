<?php
/**
 * Search Controller
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller;

use OpenTHC\Bong\CRE;

class Search extends \OpenTHC\Controller\Base
{
	public $tab = null;

	function __invoke($REQ, $RES, $ARG)
	{
		$dbc = $REQ->getAttribute('dbc');

		$sql = <<<SQL
		SELECT id, name FROM crop WHERE name LIKE :q1
		UNION ALL
		SELECT id, name FROM inventory WHERE name LIKE :q1
		SQL;

		$res = $dbc->fetchAll($sql);

		$data = [];
		$data['search_result'] = $res;

		return $RES->write( $this->render('search.php', $data) );
	}
}
