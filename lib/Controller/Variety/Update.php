<?php
/**
 * Variety Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Variety;

use Opis\JsonSchema\Validator;
use Swaggest\JsonSchema\Schema;

class Update extends \OpenTHC\Bong\Controller\Base\Update
{
	protected $_tab_name = 'variety';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);

		$schema_spec = \OpenTHC\Bong\Variety::getJSONSchema();

		// pre-validation stuff
		if (empty($source_data->id)) {
			$source_data->id = \OpenTHC\CRE\CCRS::sanatize(strtoupper($source_data->name), 100);
		}
		if (empty($source_data->type)) {
			$source_data->type = 'Hybrid';
		}

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

		// $rec = [
		// 	'id' => $source_data->id,
		// 	'license_id' => $_SESSION['License']['id'],
		// 	'name' => $source_data->name,
		// 	'data' => json_encode([
		// 		'@version' => 'openthc/2015',
		// 		'@source' => $source_data
		// 	]),
		// ];

		$dbc = $REQ->getAttribute('dbc');

		// CCRS uses Name as Primary Key, limit of 100 characters
		$arg = [
			':v0' => $source_data->id,
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->name,
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			])
		];
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash([
			'id' => $arg[':v0'],
			'name' => $arg[':n0'],
		]);


		// UPSERT
		$sql = <<<SQL
		INSERT INTO variety (id, license_id, name, hash, data)
		VALUES (:v0, :l0, :n0, :h0, :d0)
		ON CONFLICT (id, license_id) DO
		UPDATE SET
			name = :n0
			, hash = :h0
			, stat = 100
			, updated_at = now()
			, data = coalesce(variety.data, '{}'::jsonb) || :d0
		WHERE variety.hash != :h0
		RETURNING id, name, updated_at, (hash = :h0) AS hash_match
		SQL;

		// $ret = $dbc->query($sql, $arg);

		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$ret = $cmd->fetchAll();

		// $rec['data'] = json_decode($rec['data'], true);
		$this->updateStatus();

		return $RES->withJSON([
			'data' => $source_data,
			'meta' => [],
		], 201);

	}
}
