<?php
/**
 * Section Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Section;

use Opis\JsonSchema\Validator;
use Swaggest\JsonSchema\Schema;

class Create extends \OpenTHC\Bong\Controller\Base\Create
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'section';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$dbc = $REQ->getAttribute('dbc');

		$rec = [
			'id' => $_POST['id'],
			'license_id' => $_SESSION['License']['id'],
			'name' => $_POST['name'],
			'data' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $_POST
			])
		];

		$ret = $dbc->insert('section', $rec);

		return $RES->withJSON([
			'data' => $rec,
			'meta' => [],
		], 201);

	}

}
