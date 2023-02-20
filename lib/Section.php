<?php
/**
 * Section
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class Section
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
			'required' => [ 'id', 'name' ],
		];
		$schema_spec['properties']['id'] = [ 'type' => 'string' ];
		$schema_spec['properties']['name'] = [ 'type' => 'string' ];
		// $schema_spec['properties']['flag'] = [ 'type' => 'string' ];

		$schema_spec = \Opis\JsonSchema\Helper::toJSON($schema_spec);

		return $schema_spec;

	}
}
