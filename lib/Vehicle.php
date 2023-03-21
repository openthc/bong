<?php
/**
 * Vehicle
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class Vehicle
{
	/**
	 *
	 */
	static function getJSONSchema()
	{
		$schema_spec = [
			// '$schema' => '',
			'$id' => 'https://api.openthc.org/v2015/variety.json',
			'type' => 'object',
			// 'definitions' => [],
			'properties' => [],
			'required' => [ 'id', 'name', 'year', 'brand', 'model', 'color', 'tag', 'vin' ],
		];
		$schema_spec['properties']['id'] = [ 'type' => 'string' ];
		$schema_spec['properties']['name'] = [ 'type' => 'string' ];
		$schema_spec['properties']['year'] = [ 'type' => 'string' ];
		$schema_spec['properties']['brand'] = [ 'type' => 'string' ];
		$schema_spec['properties']['model'] = [ 'type' => 'string' ];
		$schema_spec['properties']['color'] = [ 'type' => 'string' ];

		$schema_spec['properties']['tag'] = [ 'type' => 'string' ];
		$schema_spec['properties']['vin'] = [ 'type' => 'string' ];

		// $schema_spec['properties'][''] = [ 'type' => 'string' ];
		// @todo ENUM
		// $schema_spec['properties']['type'] = [ 'type' => 'string' ];

		$schema_spec = \Opis\JsonSchema\Helper::toJSON($schema_spec);

		return $schema_spec;

	}
}
