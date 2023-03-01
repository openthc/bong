<?php
/**
 * Variety Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Variety;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'variety';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);

		if (empty($source_data->type)) {
			$source_data->type = 'Hybrid';
		}

		// pre-validation stuff
		if (empty($source_data->id)) {
			$source_data->id = \OpenTHC\CRE\CCRS::sanatize(strtoupper($source_data->name), 100);
		}

		// switch ($_SESSION['cre']['id']) {
		// 	case 'usa/hi':
		// 	case 'usa/nm':
		// 		// unset($source_data['id']);
		// 		break;
		// 	case 'usa/wa/ccrs':
		// 		if (empty($source_data->id)) {
		// 			$source_data->id = substr(_ulid(), 0, 16);
		// 		}
		// 		$source_data->id = substr($source_data->id, 0, 16);
		// 		break;
		// }

		$schema_spec = \OpenTHC\Bong\Variety::getJSONSchema();

		$schema_spec = \OpenTHC\Bong\Variety::getJSONSchema();

		$this->validateJSON($source_data, $schema_spec);

		$dbc = $REQ->getAttribute('dbc');

		// CCRS uses Name as Primary Key, limit of 100 characters
		$arg = [
			':v0' => \OpenTHC\CRE\CCRS::sanatize(strtoupper($source_data->name), 100),
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

		// Rewrite on Output
		switch ($_SESSION['cre']['id']) {
			case 'usa/wa/ccrs':
					// \OpenTHC\CRE\CCRS::createId();
					$source_data->id = substr(_ulid(), 0, 16);
					break;
		}

		return $RES->withJSON([
			'data' => $source_data,
			'meta' => [],
		], 201);

	}
}
