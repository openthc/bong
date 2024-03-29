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

		// Pre-validation stuff
		switch ($_SESSION['cre']['id']) {
			case 'usa/wa/ccrs':
				// CCRS uses Name as Primary Key, limit of 100 characters
				$source_data->id = \OpenTHC\CRE\CCRS::sanatize(strtoupper($source_data->name), 100);
				break;
		}

		// if (empty($source_data->type)) {
			$source_data->type = 'Hybrid';
		// }

		$schema_spec = \OpenTHC\Bong\Variety::getJSONSchema();

		$this->validateJSON($source_data, $schema_spec);

		$sql = $this->getUpsertSQL();
		$arg = [
			':o0' => $source_data->id,
			':l0' => $_SESSION['License']['id'],
			':n0' => $source_data->name,
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $source_data
			])
		];
		$arg[':h0'] = \OpenTHC\CRE\Base::objHash([
			'id' => $source_data->id,
			'name' => $source_data->name,
		]);

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

		$output_data = $this->getReturnObject($dbc, $source_data->id);
		if ($output_data->stat >= 200) {
			$ret_code = $output_data->stat;
		}

		// Rewrite on Output
		switch ($_SESSION['cre']['id']) {
			case 'usa/wa/ccrs':
				$output_data->id = $ARG['id'];
				break;
		}

		return $RES->withJSON([
			'data' => $output_data,
			'meta' => [
				'_ret' => $ret,
			],
		], $ret_code);

	}
}
