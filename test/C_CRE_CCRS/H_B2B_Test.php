<?php
/**
 * Test B2B Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class H_B2B_Test extends \OpenTHC\Bong\Test\Base_Case
{
	function test_create()
	{
		$x = getenv('OPENTHC_TEST_BASE');
		$this->assertNotEmpty($x);
	}

	function test_create_duplicate()
	{
		$x = getenv('OPENTHC_TEST_BASE');
		$this->assertNotEmpty($x);
	}

	function test_search()
	{
		$x = getenv('OPENTHC_TEST_BASE');
		$this->assertNotEmpty($x);
	}

	function test_update()
	{
		$x = getenv('OPENTHC_TEST_BASE');
		$this->assertNotEmpty($x);
	}

	function test_delete()
	{
		$x = getenv('OPENTHC_TEST_BASE');
		$this->assertNotEmpty($x);
	}

}
