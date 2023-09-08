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

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$dbc = $REQ->getAttribute('dbc');

		$res = [];

		$tab_list = [
			'crop',
			'inventory',
			'b2b_outgoing_item',
			'b2b_incoming_item',
			'b2b_outgoing',
			'b2b_incoming',
		];

		foreach ($tab_list as $tab) {
			$sql = <<<SQL
			SELECT id, name
			FROM $tab
			WHERE name LIKE :q1
			   OR text::text LIKE :q1
			SQL;

			$res[$tab] = $dbc->fetchAll($sql, $arg);
		}

		$data = [];
		$data['search_result'] = $res;

		return $RES->write( $this->render('search.php', $data) );
	}
}
