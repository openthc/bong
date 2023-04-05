<?php
/**
 * Crop Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Crop;

class Update extends \OpenTHC\Bong\Controller\Base\Update
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'crop';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data['id'] = $ARG['id'];
		$source_data['qty'] = floatval($source_data['qty']);
		if (empty($source_data['name'])) {
			$source_data['name'] = $source_data['id'];
		}

		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);

		$schema_spec = \OpenTHC\Bong\Crop::getJSONSchema();
		$this->validateJSON($source_data, $schema_spec);

		// UPSERT
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
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash($arg[':d0']);

		$dbc = $REQ->getAttribute('dbc');
		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$hit = $cmd->rowCount();

		// $ret = $dbc->query($sql, $arg);
		// if (1 == $ret) {
		// 	return $RES->withJSON([
		// 		'data' => [
		// 			'id' => $arg[':o1'],
		// 			'name' => $arg[':n0'],
		// 			'hash' => $arg[':h0'],
		// 		],
		// 		'meta' => [],
		// 	]);
		// }

		$ret_code = 200;
		if ($ret['stat'] >= 200) {
			$ret_code = $ret['stat'];
		}

		// $ret_code = 200;
		// switch ($hit) {
		// 	case 0:
		// 		// No INSERT or UPDATE
		// 		return $RES->withJSON([
		// 			'data' => $source_data,
		// 			'meta' => [],
		// 		], 202);
		// 		break;
		// 	case 1:
		// 		// Perfection
		// 		$ret = $cmd->fetch();
		// 		if (empty($ret['updated_at'])) {
		// 			$ret_code = 201;
		// 		}
		// 		break;
		// 	default:
		// 		throw new \Exception('Invalid Database State [CIA-073]');
		// }

		$this->updateStatus();

		$output_data = $this->getReturnObject($dbc, $source_data->id);

		return $RES->withJSON([
			'data' => $output_data,
			'meta' => [],
		], $ret_code);

	}

}
