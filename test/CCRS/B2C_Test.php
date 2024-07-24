<?php
/**
 * Test B2C Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\CCRS;

class B2C_Test extends \OpenTHC\Bong\Test\CCRS\Base_Case
{
	function test_create()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
	}

	function test_create_duplicate()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
	}

	function test_search()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
	}

	function test_update()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
	}

	function test_delete()
	{
		$x = OPENTHC_TEST_ORIGIN;
		$this->assertNotEmpty($x);
	}

}
