<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class H_B2B_Test extends \Test\Base_Case
{
	function test_env()
	{
		$x = getenv('OPENTHC_TEST_HOST');
		$this->assertNotEmpty($x);
	}

}
