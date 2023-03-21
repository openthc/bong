<?php
/**
 * Section Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Section;

use Opis\JsonSchema\Validator;
use Swaggest\JsonSchema\Schema;

class Update extends \OpenTHC\Bong\Controller\Base\Update
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'section';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data['id'] = $ARG['id'];

		// switch ($_SESSION['cre']['id']) {
		// 	case 'usa/hi':
		// 	case 'usa/nm':
		// 		// unset($source_data['id']);
		// 		break;
		// 	case 'usa/wa/ccrs':
		// 		if (empty($source_data['id'])) {
		// 			$source_data['id'] = substr(_ulid(), 0, 16);
		// 		}
		// 		$source_data['id'] = substr($source_data['id'], 0, 16);
		// 		break;
		// }

		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);
		$schema_spec = \OpenTHC\Bong\Section::getJSONSchema();
		$this->validateJSON($source_data, $schema_spec);

		$dbc = $REQ->getAttribute('dbc');

		$sql = $this->getUpsertSQL();

		$arg = [
			':o0' => $source_data->id,
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->name,
			':h0' => '-',
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data,
			]),
		];
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash($source_data);

		$dbc = $REQ->getAttribute('dbc');
		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$hit = $cmd->rowCount();
		$ret = $cmd->fetchAll();

		$ret_code = 200;
		if ($ret['stat'] >= 200) {
			$ret_code = $ret['stat'];
		}

		$this->updateStatus();

		$output_data = $dbc->fetchRow('SELECT * FROM section WHERE license_id = :l0 AND id = :s0', [
			':l0' => $_SESSION['License']['id'],
			':s0' => $source_data->id,
		]);
		$output_data['data'] = json_decode($output_data['data']);

		return $RES->withJSON([
			'data' => $output_data,
			'meta' => [],
		], $ret_code);

	}
}
