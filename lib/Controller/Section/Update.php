<?php
/**
 * Section Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Section;

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

		// UPSERT
		$sql = $this->getUpsertSQL();
		$arg = [
			':o0' => $source_data->id,
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->name,
			':h0' => \OpenTHC\CRE\Base::objHash($source_data),
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			]),
		];

		$dbc = $REQ->getAttribute('dbc');
		$cmd = $dbc->prepare($sql);
		$res = $cmd->execute($arg);
		$hit = $cmd->rowCount();
		$ret = $cmd->fetchAll();

		// On INSERT
		// $res == true
		// $hit == 1
		// $ret == [ 0 => [ stat=100, updated-at set to very recent) ] ]

		// On UPDATE w/Exactly The Same
		// $res == true
		// $hit == 0
		// $ret == [ ]

		// On Update w/Change
		// $res == true
		// $hit == 1
		// $ret = [ 0 => [ stat=100, updated_at=now() ]]

		$ret_code = 200;
		// if ($ret['stat'] >= 200) {
		// 	$ret_code = $ret['stat'];
		// }

		$this->updateStatus();

		$output_data = $this->getReturnObject($dbc, $source_data->id);
		if ($output_data->stat >= 200) {
			$ret_code = $output_data->stat;
		}

		return $RES->withJSON([
			'data' => $output_data,
			'meta' => [],
		], $ret_code);

	}
}
