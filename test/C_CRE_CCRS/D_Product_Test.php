<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class D_Product_Test extends \OpenTHC\Bong\Test\Base_Case
{
	function test_env()
	{
		$x = getenv('OPENTHC_TEST_BASE');
		$this->assertNotEmpty($x);
	}

}