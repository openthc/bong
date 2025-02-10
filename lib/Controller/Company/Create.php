<?php
/**
 * Company
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Company;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	protected $_tab_name = 'company';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		// $source_data->qty = floatval($source_data->qty);
		// $schema_spec = \OpenTHC\Bong\Crop::getJSONSchema();
		// $this->validateJSON($source_data, $schema_spec);

		$dbc = $REQ->getAttribute('dbc');

		// Match Service ?
		// Auth Tokens Somehwere?
		$sql = <<<SQL
		SELECT id
		FROM company
		WHERE id = :cp0
		SQL;
		$arg = [];
		$arg[':cp0'] = $source_data->id;

		$chk = $dbc->fetchOne($sql, $arg);
		if ( ! empty($chk)) {
			return $RES->withJSON([
				'data' => $chk,
				'meta' => [ 'note' => 'Company Exists [CCC-041]' ],
			], 409);
		}
		$rec = [
			'id' => $source_data->id,
			'name' => $source_data->name,
			// 'data' => json_encode([
			// 		'@version' => 'openthc/2015',
			// 		'@source' => $source_data
			// ]),
		];

		$ret = $dbc->insert($this->_tab_name, $rec);

		return $RES->withJSON([
			'data' => $source_data,
			'meta' => [],
		], 201);

	}

}
