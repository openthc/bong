<?php
/**
 * Variety Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Variety;

class Update extends \OpenTHC\Bong\Controller\Base\Update
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

		// pre-validation stuff
		if (empty($source_data->id)) {
			$source_data->id = \OpenTHC\CRE\CCRS::sanatize(strtoupper($source_data->name), 100);
		}
		if (empty($source_data->type)) {
			$source_data->type = 'Hybrid';
		}

		$schema_spec = \OpenTHC\Bong\Variety::getJSONSchema();

		$this->validateJSON($source_data, $schema_spec);

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
		$dbc = $REQ->getAttribute('dbc');
		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$hit = $cmd->rowCount();
		$ret = $cmd->fetchAll();

		$this->updateStatus();

		// Rewrite on Output
		switch ($_SESSION['cre']['id']) {
			case 'usa/wa/ccrs':
				$source_data->id = $ARG['id'];
				break;
		}

		return $RES->withJSON([
			'data' => $source_data,
			'meta' => [],
		], 201);

	}
}
