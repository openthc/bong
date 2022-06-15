<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class B_Variety_Test extends \OpenTHC\Bong\Test\Base_Case
{
	function test_env()
	{
		$x = getenv('OPENTHC_TEST_HOST');
		$this->assertNotEmpty($x);
	}

}
