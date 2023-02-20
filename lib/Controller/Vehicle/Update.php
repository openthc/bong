<?php
/**
 * Vehicle Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Vehicle;

use Opis\JsonSchema\Validator;
use Swaggest\JsonSchema\Schema;

class Update extends \OpenTHC\Bong\Controller\Base\Status
{
	protected $_tab_name = 'vehicle';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;

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

		$schema_spec = \OpenTHC\Bong\Vehicle::getJSONSchema();

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

		$rec = [
			'id' => $source_data->id,
			'license_id' => $_SESSION['License']['id'],
			'data' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			]),
		];

		$dbc = $REQ->getAttribute('dbc');
		// $ret = $dbc->insert($this->_tab_name, $rec);

		$rec['data'] = json_decode($rec['data'], true);

		$this->updateStatus();

		return $RES->withJSON([
			'data' => $rec,
			'meta' => [],
		], 201);

	}
}
