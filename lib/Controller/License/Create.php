<?php
/**
 * License
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\License;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	protected $_tab_name = 'license';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		$source_data->company_id = $_SESSION['Company']['id'];

		$dbc = $REQ->getAttribute('dbc');

		// Check for Duplicate
		$sql = <<<SQL
		SELECT id
		FROM license
		WHERE id = :lic0 AND company_id = :cmp0
		SQL;
		$arg = [];
		$arg[':lic0'] = $source_data->id;
		$arg[':cmp0'] = $source_data->company_id;

		$chk = $dbc->fetchOne($sql, $arg);
		if ( ! empty($chk)) {
			return $RES->withJSON([
				'data' => $chk,
				'meta' => [ 'note' => 'Company Exists [CCC-041]' ],
			], 409);
		}









		$rec = [
			'id' => $source_data->id,
			'company_id' => $source_data->company_id,
			'code' => $source_data->code,
			'name' => $source_data->name,
			'stat' => 200,
		];
		// $rec['hash'] = sha1(json_encode($rec));

		$res = $dbc->insert('license', $rec);

		return $RES->withJSON([
			'data' => $rec,
			'meta' => [],
		], 201);

	}

}
