<?php
/**
 * Product Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Product;

use Opis\JsonSchema\Validator;
use Swaggest\JsonSchema\Schema;

class Update extends \OpenTHC\Bong\Controller\Base\Update
{
	protected $_tab_name = 'product';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$_POST['name'] = trim($_POST['name']);
		$_POST['type'] = trim($_POST['type']);

		// Name Check
		if (empty($_POST['name'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [
					'note' => 'Invalid Product Name [CPU-033]'
				],
			], 400);
		}

		// Type
		if (empty($_POST['type'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [
					'note' => 'Invalid Product Type [CPU-044]'
				],
			], 400);
		}

		$source_data = $_POST;
		$source_data['id'] = $ARG['id'];

		// Some kind of CRE Handler?
		switch ($_SESSION['cre']['id']) {
			case 'usa/hi':
			case 'usa/nm':
				// unset($source_data['id']);
				break;
			case 'usa/wa/ccrs':
				if (empty($source_data['id'])) {
					$source_data['id'] = substr(_ulid(), 0, 16);
				}
				$source_data['id'] = substr($source_data['id'], 0, 16);
				break;
		}

		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		$schema_spec = \OpenTHC\Bong\Product::getJSONSchema();
		$this->validateJSON($source_data, $schema_spec);

		$sql = $this->getUpsertSQL();
		$arg = [
			':o0' => $source_data->id,
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->name,
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			]),
		];
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash( [
			'id' => $source_data->id,
			'name' => $source_data->name,
			'type' => $source_data->type,
			'package' => $source_data->package,
		]);

		$dbc = $REQ->getAttribute('dbc');
		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$hit = $cmd->rowCount();
		$ret = $cmd->fetchAll();

		$this->updateStatus();

		$output_data = $this->getReturnObject($dbc, $source_data->id);

		return $RES->withJSON([
			'data' => $output_data,
			'meta' => [],
		], 201);

	}
}
