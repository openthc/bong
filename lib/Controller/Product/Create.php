<?php
/**
 * Product Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Product;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'product';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);

		// pre-validation stuff
		if (empty($source_data->id)) {
			$source_data->id = substr(_ulid(), 0, 16);
		}

		$schema_spec = \OpenTHC\Bong\Product::getJSONSchema();

		$this->validateJSON($source_data, $schema_spec);

		$dbc = $REQ->getAttribute('dbc');

		// Check Object Exists
		$RES = $this->checkObjectExists($RES, $dbc, $source_data->id);
		if (200 != $RES->getStatusCode()) {
			return $RES;
		}

		// CCRS uses Name as Primary Key, limit of 100 characters
		$arg = [
			':v0' => $source_data->id,
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->name,
			':h0' => \OpenTHC\CRE\Base::objHash($source_data),
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			])
		];

		// UPSERT
		$sql = <<<SQL
		INSERT INTO product (id, license_id, name, hash, data)
		VALUES (:v0, :l0, :n0, :h0, :d0)
		ON CONFLICT (id, license_id) DO
		UPDATE SET
			name = :n0
			, hash = :h0
			, stat = 100
			, updated_at = now()
			, data = coalesce(product.data, '{}'::jsonb) || :d0
		WHERE product.hash != :h0
		RETURNING id, name, updated_at, (hash = :h0) AS hash_match
		SQL;

		// $ret = $dbc->query($sql, $arg);

		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$ret = $cmd->fetchAll();

		$this->updateStatus();

		return $RES->withJSON([
			'data' => $source_data,
			'meta' => [],
		], 201);

	}
}
