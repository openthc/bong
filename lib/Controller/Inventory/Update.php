<?php
/**
 * Inventory Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Inventory;

use Opis\JsonSchema\Validator;
// use Swaggest\JsonSchema\Schema;

class Update extends \OpenTHC\Bong\Controller\Base\Update
{
	protected $_tab_name = 'inventory';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		$source_data->qty = floatval($source_data->qty);

		$validator = new Validator();
		$res_json = $validator->validate($source_data, $schema_spec);
		if ( ! $res_json->isValid()) {
			__exit_text($res_json->error()->__toString(), 500);
		}

		// UPSERT IT
		$sql = <<<SQL
		INSERT INTO lot (id, license_id, name, hash, data) VALUES (:o1, :l0, :n0, :h0, :d0)
		ON CONFLICT (id) DO
		UPDATE SET updated_at = now(), stat = 100, name = EXCLUDED.name, hash = EXCLUDED.hash, data = coalesce(lot.data, '{}'::jsonb) || :d0
		WHERE lot.id = EXCLUDED.id AND lot.license_id = EXCLUDED.license_id
		SQL;

		$arg = [
			':o1' => $ARG['id'],
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->name,
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			]),
		];
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash($source_data);

		$ret = $dbc->query($sql, $arg);
		if (1 == $ret) {
			return $RES->withJSON([
				'data' => [
					'id' => $ARG['id'],
					'name' => $_POST['name']
				],
				'meta' => $_POST,
			]);
		}

		$this->updateStatus();

		return $RES->withJSON([
			'data' => $source_data,
			'meta' => [],
		], 200);

	}

}
