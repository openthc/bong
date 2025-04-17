<?php
/**
 * Crop
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class Crop
{
	/**
	 *
	 */
	static function getJSONSchema()
	{
		$schema_spec = [
			// '$schema' => '',
			'$id' => 'https://api.openthc.org/v2015/crop.json',
			'type' => 'object',  // ("null", "boolean", "object", "array", "number", or "string")
			// 'definitions' => [],
			'properties' => [],
			'required' => [ 'id', 'qty', 'section', 'variety' ],
		];
		$schema_spec['properties']['id'] = [ 'type' => 'string' ];
		$schema_spec['properties']['qty'] = [ 'type' => 'number' ];
		// $schema_spec['properties']['name'] = [ 'type' => 'string' ];
		$schema_spec['properties']['section'] = [
			'type' => 'object',
			'required' => [ 'id' ],
			'properties' => [
				'id' => [ 'type' => 'string' ],
				'name' => [ 'type' => 'string' ],
			]
		];
		$schema_spec['properties']['variety'] = [
			'type' => 'object',
			'required' => [ 'id' ],
			'properties' => [
				'id' => [ 'type' => 'string' ],
				'name' => [ 'type' => 'string' ],
			]
		];

		$schema_spec = \Opis\JsonSchema\Helper::toJSON($schema_spec);

		return $schema_spec;

	}
}
