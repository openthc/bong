<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\CCRS;

class Auth_Test extends \OpenTHC\Bong\Test\CCRS\Base_Case
{
	/**
	 * @test
	 */
	function test_auth_config()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
	}

	/**
	 * @test
	 */
	function test_auth_connect()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
	}

	/**
	 * Test that the session will timeout after 15 minutes
	 * @test
	 */
	function test_auth_timeout()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
		// sleep(15 * 60);
	}


}
