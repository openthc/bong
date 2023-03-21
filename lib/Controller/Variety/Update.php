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
				$source_data->id = \OpenTHC\CRE\CCRS::sanatize(strtoupper($source_data->name), 100);
				break;
		}

		if (empty($source_data->type)) {
			$source_data->type = 'Hybrid';
		}

		$schema_spec = \OpenTHC\Bong\Variety::getJSONSchema();

		$this->validateJSON($source_data, $schema_spec);

		// CCRS uses Name as Primary Key, limit of 100 characters
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
			'id' => $arg[':v0'],
			'name' => $arg[':n0'],
		]);

		$sql = $this->getUpsertSQL();

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

		$output_data = $this->getReturnObject($source_data->id);

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
