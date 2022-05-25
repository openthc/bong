<?php
/**
 * Customized Response Object
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class Response extends \Slim\Http\Response
{
	/**
	 * Update JSON
	 */
	function withJSON($data, $code=200, $flag=null)
	{
		if (empty($flag)) {
			$flag = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		} else {
			$flag = ($flag | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		return parent::withJSON($data, $code, $flag);
	}
}
