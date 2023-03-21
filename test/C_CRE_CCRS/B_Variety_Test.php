<?php
/**
 * Test Variety Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class B_Variety_Test extends \OpenTHC\Bong\Test\Base_Case
{
	function test_create()
	{
		$cre = $this->getBONGtoCCRS();

		$res = $cre->variety()->create([
			'id' => _ulid(),
			'name' => sprintf('Test Variety %s', $this->_pid),
		]);

		$this->assertNotEmpty($res);
		$this->assertIsArray($res);
		$this->assertArrayHasKey('code', $res);
		$this->assertArrayHasKey('data', $res);
		$this->assertArrayHasKey('meta', $res);

		$this->assertEquals(201, $res['code']);

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
