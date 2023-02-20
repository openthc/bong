<?php
/**
 * Variety
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class Variety
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
			'required' => [ 'id', 'name', 'type' ],
		];
		$schema_spec['properties']['id'] = [ 'type' => 'string' ];
		$schema_spec['properties']['name'] = [ 'type' => 'string' ];
		// @todo ENUM
		$schema_spec['properties']['type'] = [ 'type' => 'string' ];

		$schema_spec = \Opis\JsonSchema\Helper::toJSON($schema_spec);

		return $schema_spec;

	}
}
