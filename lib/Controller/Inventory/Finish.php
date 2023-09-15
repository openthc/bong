<?php
/**
 * Inventory Finish
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Inventory;

class Finish extends \OpenTHC\Controller\Base
{
	use \OpenTHC\Traits\JSONValidator;

	protected $_tab_name = 'inventory';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$source_data = $_POST;
		$source_data = \Opis\JsonSchema\Helper::toJSON($source_data);


	}

}
