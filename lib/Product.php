<?php
/**
 * Product
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class Product
{
	/**
	 *
	 */
	static function getJSONSchema()
	{
		$schema_spec = [
			// '$schema' => '',
			'$id' => 'https://api.openthc.org/v2015/product.json',
			'type' => 'object',  // ("null", "boolean", "object", "array", "number", or "string")
			// 'definitions' => [],
			'properties' => [],
			'required' => [ 'id', 'name', 'type', 'uom' ],
		];
		$schema_spec['properties']['id'] = [ 'type' => 'string' ];
		$schema_spec['properties']['name'] = [ 'type' => 'string' ];
		$schema_spec['properties']['note'] = [ 'type' => 'string' ];
		$schema_spec['properties']['type'] = [ 'type' => 'string' ];

		// $schema_spec['properties']['package'] = [
		// 	'type' => 'object',
		// 	'properties' => [
		// 		'unit_count' => [ 'type' => 'number' ],
		// 		'unit_weight' => [ 'type' => 'number' ],
		// 		'unit_volume' => [ 'type' => 'number' ],
		// 	]
		// ];

		// $schema_spec['properties']['package_size'] = [ 'type' => 'number' ];

		// @todo Make Enum
		$schema_spec['properties']['uom'] = [ 'type' => 'string' ];

		$schema_spec = \Opis\JsonSchema\Helper::toJSON($schema_spec);

		return $schema_spec;

	}
}
