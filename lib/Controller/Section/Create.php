<?php
/**
 * Section Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Section;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'section';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);

		$schema_spec = \OpenTHC\Bong\Section::getJSONSchema();

		$this->validateJSON($source_data, $schema_spec);

		$dbc = $REQ->getAttribute('dbc');

		// Check Object Exists
		$RES = $this->checkObjectExists($RES, $dbc, $source_data->id);
		if (200 != $RES->getStatusCode()) {
			return $RES;
		}

		$rec = [
			'id' => $source_data->id,
			'license_id' => $_SESSION['License']['id'],
			'name' => $source_data->name,
			'hash' => \OpenTHC\CRE\Base::objHash($source_data),
			'data' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $_POST
			])
		];

		// $chk = new \OpenTHC\Section($dbc, $rec['id']);
		// if (empty($chk['id'])) {
		// }
		$ret = $dbc->insert('section', $rec);

		$ret_code = 201;
		$output_data = $this->getReturnObject($dbc, $source_data->id);
		// if ($output_data->stat >= 200) {
		// 	$ret_code = $output_data->stat;
		// }

		$this->updateStatus();

		return $RES->withJSON([
			'data' => $output_data,
			'meta' => [],
		], $ret_code);

	}

}
