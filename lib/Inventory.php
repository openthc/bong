<?php
/**
 * Inventory
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class Inventory
{
	/**
	 *
	 */
	static function getJSONSchema()
	{
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

		return $schema_spec;

	}
}
