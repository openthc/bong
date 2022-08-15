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
	 * Constructor
	 */
	function __construct($c=200, $h=null)
	{
		$h = new \Slim\Http\Headers([
			'content-type' => 'text/html; charset=utf-8'
		]);

		parent::__construct($c, $h);

	}

	/**
	 * Update JSON
	 */
	function withJSON($data, $code=200, $flag=null)
	{
		if (empty($flag)) {
			$flag = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		} else {
			$flag = ($flag | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		return parent::withJSON($data, $code, $flag);
	}
}
