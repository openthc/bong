<?php
/**
 * Inventory Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Inventory;

use Opis\JsonSchema\Validator;
use Swaggest\JsonSchema\Schema;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	protected $_tab_name = 'inventory';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data['qty'] = floatval($source_data['qty']);


		switch ($_SESSION['cre']['id']) {
			case 'usa/hi':
			case 'usa/nm':
				// unset($source_data['id']);
				break;
			case 'usa/wa/ccrs':
				if (empty($source_data['id'])) {
					$source_data['id'] = substr(_ulid(), 0, 16);
				}
				$source_data['id'] = substr($source_data['id'], 0, 16);
				break;
		}

		// if (empty($source_data['product']) && ! empty($source_data['product_id'])) {
		// 	$source_data['product'] = [
		// 		'id' => $source_data['product_id'],
		// 	];
		// 	unset($source_data['product_id']);
		// }
		// if (empty($source_data['variety']) && ! empty($source_data['variety_id'])) {
		// 	$source_data['variety'] = [
		// 		'id' => $source_data['variety_id'],
		// 	];
		// 	unset($source_data['variety_id']);
		// }
		// if (empty($source_data['section']) && ! empty($source_data['section_id'])) {
		// 	$source_data['section'] = [
		// 		'id' => $source_data['section_id'],
		// 	];
		// 	unset($source_data['section_id']);
		// }

		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);

		$schema_spec = [
			// '$schema' => '',
			'$id' => 'https://api.openthc.org/v2015/inventory.json',
			'type' => 'object',  // ("null", "boolean", "object", "array", "number", or "string")
			// 'definitions' => [],
			'properties' => [],
			'required' => [ 'id', 'qty', 'section', 'variety', 'product' ],
		];
		$schema_spec['properties']['id'] = [ 'type' => 'string' ];
		$schema_spec['properties']['qty'] = [ 'type' => 'number' ];
		// $schema_spec['properties']['name'] = [ 'type' => 'string' ];
		$schema_spec['properties']['section'] = [
			'type' => 'object',
			'required' => [ 'id', 'name' ],
			'properties' => [
				'id' => [ 'type' => 'string' ],
				'name' => [ 'type' => 'string' ],
			]
		];
		$schema_spec['properties']['variety'] = [
			'type' => 'object',
			'required' => [ 'id', 'name' ],
			'properties' => [
				'id' => [ 'type' => 'string' ],
				'name' => [ 'type' => 'string' ],
			]
		];
		$schema_spec['properties']['product'] = [
			'type' => 'object',
			'required' => [ 'id', 'name' ],
			'properties' => [
				'id' => [ 'type' => 'string' ],
				'name' => [ 'type' => 'string' ],
			]
		];
		$schema_spec = \Opis\JsonSchema\Helper::toJSON($schema_spec);

		$schema = Schema::import($schema_spec);
		try {
			$res_json = $schema->in($source_data);
		} catch (\Exception $e) {
			__exit_text($e->getMessage(), 500);
		}

		$validator = new Validator();
		$res_json = $validator->validate($source_data, $schema_spec);
		if ( ! $res_json->isValid()) {
			__exit_text($res_json->error()->__toString(), 500);
		}

		$rec = [
			'id' => $source_data->id,
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
