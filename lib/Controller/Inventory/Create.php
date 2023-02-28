<?php
/**
 * Inventory Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Inventory;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'inventory';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		$source_data->qty = floatval($source_data->qty);


		switch ($_SESSION['cre']['id']) {
			case 'usa/hi':
			case 'usa/nm':
				// unset($source_data['id']);
				break;
			case 'usa/wa/ccrs':
				if (empty($source_data->id)) {
					$source_data->id = substr(_ulid(), 0, 16);
				}
				$source_data->id = substr($source_data->id, 0, 16);
				break;
		}

		$schema_spec = \OpenTHC\Bong\Inventory::getJSONSchema();
		$this->validateJSON($source_data, $schema_spec);

		$rec = [
			'id' => $source_data->id,
			'name' => $source_data->name ?: $source_data->id,
			'license_id' => $_SESSION['License']['id'],
			'data' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			]),
		];

		$dbc = $REQ->getAttribute('dbc');
		$ret = $dbc->insert('lot', $rec);

		$rec['data'] = json_decode($rec['data'], true);

		$this->updateStatus();

		return $RES->withJSON([
			'data' => $rec,
			'meta' => [],
		], 201);

	}
}
