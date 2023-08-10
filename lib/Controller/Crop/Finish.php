<?php
/**
 * Crop Finish
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Crop;

class Finish extends \OpenTHC\Controller\Base
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'crop_finish';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		// 'created_at' => $x['created_at'],
		// 'updated_at' => $x['updated_at'],
		// 'finished_at' => $x['finished_at'],
		// // PlantDied, Contamination, TooMuchWater, TooLittleWater, MalePlant, Mites, Other
		// 'reason' => 'Other',
		// 'note' => '',
		// 'method' => 'Other', // • Co

		// $schema_spec = $this->getJSONSchema();
		// $this->validateJSON($source_data, $schema_spec);

		// UPSERT
		$sql = $this->getUpsertSQL();
		$arg = [
			':o0' => $ARG['id'],
			':l0' => $_SESSION['License']['id'],
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			]),
		];
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash($arg[':d0']);

		$dbc = $REQ->getAttribute('dbc');
		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$hit = $cmd->rowCount();

		$ret_code = 200;
		if ($ret['stat'] >= 200) {
			$ret_code = $ret['stat'];
		}

		$this->updateStatus();

		$output_data = $this->getReturnObject($dbc, $source_data->id);

		return $RES->withJSON([
			'data' => $output_data,
			'meta' => [],
		], $ret_code);

	}

	/**
	 *
	 */
	function getJSONSchema()
	{
		$schema_spec = [
			'$id' => 'https://api.openthc.org/v2015/crop/finish.json',
			'type' => 'object',
			'properties' => [],
			'required' => [ 'id', 'reason', 'method' ],
		];
		$schema_spec['properties']['id'] = [ 'type' => 'string' ];
		// $schema_spec['properties']['qty'] = [ 'type' => 'number' ];
		$schema_spec['properties']['reason'] = [ 'type' => 'string' ];
		$schema_spec['properties']['method'] = [ 'type' => 'string' ];

		// $schema_spec['properties']['section'] = [
		// 	'type' => 'object',
		// 	'required' => [ 'id', 'name' ],
		// 	'properties' => [
		// 		'id' => [ 'type' => 'string' ],
		// 		'name' => [ 'type' => 'string' ],
		// 	]
		// ];
		// $schema_spec['properties']['variety'] = [
		// 	'type' => 'object',
		// 	'required' => [ 'id', 'name' ],
		// 	'properties' => [
		// 		'id' => [ 'type' => 'string' ],
		// 		'name' => [ 'type' => 'string' ],
		// 	]
		// ];

		$schema_spec = \Opis\JsonSchema\Helper::toJSON($schema_spec);

		return $schema_spec;
	}

}
