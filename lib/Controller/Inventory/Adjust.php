<?php
/**
 * Inventory Adjust
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Inventory;

class Adjust extends \OpenTHC\Bong\Controller\Base\Update
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'inventory_adjust';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data['inventory'] = [
			'id' => $ARG['id']
		];
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		// $source_data->inventory_id = $ARG['id'];
		$source_data->qty = floatval($source_data->qty);

		// $schema_spec = \OpenTHC\Bong\Inventory\Adjust::getJSONSchema();
		// $this->validateJSON($source_data, $schema_spec);

		// UPSERT IT
		$sql = <<<SQL
		INSERT INTO inventory_adjust (id, license_id, inventory_id, name, hash, data) VALUES (:o1, :l0, :i1, :n0, :h0, :d0)
		ON CONFLICT (id) DO
		UPDATE SET updated_at = now()
			, stat = 100
			, name = EXCLUDED.name
			, hash = EXCLUDED.hash
			, data = coalesce(inventory_adjust.data, '{}'::jsonb) || :d0
		WHERE inventory_adjust.hash != :h0
		RETURNING id, name, updated_at
		SQL;

		$arg = [
			':o1' => $source_data->id,
			':i1' => $ARG['id'],
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->type,
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			]),
		];
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash($source_data);

		$dbc = $REQ->getAttribute('dbc');
		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$hit = $cmd->rowCount();

		$ret_code = 200;
		switch ($hit) {
			case 0:
				// No INSERT or UPDATE
				$ret_code = 202; // ?
				break;
			case 1:
				// Perfection
				$ret = $cmd->fetch();
				// $ret = $cmd->fetchAll();
				if (empty($ret['updated_at'])) {
					$ret_code = 201;
				}
				break;
			default:
				throw new \Exception('Invalid Database State [CIA-073]');
		}

		// Redis Status
		$this->updateStatus();

		return $RES->withJSON([
			'data' => $source_data,
			'meta' => [
				'hit' => $hit,
				'ret' => $ret,
			],
		], $ret_code);

	}

}
