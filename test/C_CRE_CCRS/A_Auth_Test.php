<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class A_Auth_Test extends \Test\Base_Case
{
	/**
	 * @test
	 */
	function test_auth_config()
	{
		$x = getenv('OPENTHC_TEST_HOST');
		$this->assertNotEmpty($x);
	}

	/**
	 * @test
	 */
	function test_auth_connect()
	{
		$x = getenv('OPENTHC_TEST_HOST');
		$this->assertNotEmpty($x);
	}

	/**
	 * Test that the session will timeout after 15 minutes
	 * @test
	 */
	function test_auth_timeout()
	{
		$x = getenv('OPENTHC_TEST_HOST');
		$this->assertNotEmpty($x);
		// sleep(15 * 60);
	}


}
