<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\A_Core;

class A_Config_Test extends \OpenTHC\Bong\Test\Base_Case
{
	function test_env()
	{
		$x = getenv('OPENTHC_TEST_BASE');
		$this->assertNotEmpty($x);
	}

	/**
	 */
	function test_psk()
	{
		$x = \OpenTHC\Config::get('psk');
		$this->assertNotEmpty($x);
	}

	/**
	 */
	function test_tz()
	{
		$x = \OpenTHC\Config::get('tz');
		$this->assertNotEmpty($x);
	}

}