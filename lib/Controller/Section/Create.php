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

		$rec = [
			'id' => $_POST['id'],
			'license_id' => $_SESSION['License']['id'],
			'name' => $_POST['name'],
			'hash' => \OpenTHC\CRE\Base::objHash($source_data),
			'data' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $_POST
			])
		];

		$dbc = $REQ->getAttribute('dbc');
		$dbc->query('BEGIN');
		// $chk = new \OpenTHC\Section($dbc, $rec['id']);
		// if (empty($chk['id'])) {
		// }
		$sql = <<<SQL
		SELECT id, license_id, name, hash, stat, data
		FROM {$this->_tab_name}
		WHERE license_id = :l0 AND id = :s0
		SQL;
		$chk = $dbc->fetchRow($sql, [
			':l0' => $_SESSION['License']['id'],
			':s0' => $rec['id'],
		]);
		if ( ! empty($chk['id'])) {

			$ret_code = 409;
			$chk['stat'] = $ret_code;

			return $RES->withJSON([
				'data' => $chk,
				'meta' => [ 'note' => 'Object Exists [CSC-060]' ],
			], $ret_code);

		}

		$ret = $dbc->insert('section', $rec);

		$dbc->query('COMMIT');

		$ret_code = 201;

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
