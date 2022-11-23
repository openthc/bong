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
	 * @test
	 */
	function all_config()
	{
		$key_list = [
			'cre/usa/wa/ccrs/username',
			'cre/usa/wa/ccrs/password',
			'cre/usa/wa/ccrs/service-key',
			'database',
			'tz',
		];

		foreach ($key_list as $k) {
			$x = \OpenTHC\Config::get($k);
			$this->assertNotEmpty($x, sprintf('%s is empty', $k));
		}

	}

}
