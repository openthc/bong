<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\Unit;

class Config_Test extends \OpenTHC\Bong\Test\Base
{
	function test_defined()
	{
		$key_list = [
			'OPENTHC_TEST_ORIGIN',
			'OPENTHC_TEST_CLIENT_SERVICE_ID',
			'OPENTHC_TEST_CLIENT_SERVICE_SK',
			'OPENTHC_TEST_BIOTRACK_COMPANY',
			'OPENTHC_TEST_BIOTRACK_PASSWORD',
			'OPENTHC_TEST_BIOTRACK_USERNAME',
			'OPENTHC_TEST_CCRS_COMPANY_ID',
			'OPENTHC_TEST_CCRS_LICENSE_ID',
			'OPENTHC_TEST_LICENSE_KEY',
		];

		foreach ($key_list as $k) {
			$this->assertTrue(defined($k), "CONST '$k' is not defined");
			$this->assertNotEmpty(constant($k), "CONST '$k' is empty");
		}

	}

	/**
	 * @test
	 */
	function all_config()
	{
		$key_list = [
			'cre/usa/wa/ccrs/tz',
			'cre/usa/wa/ccrs/username',
			'cre/usa/wa/ccrs/password',
			'cre/usa/wa/ccrs/service-key',
		];
		foreach ($key_list as $k) {
			$x = \OpenTHC\Config::get($k);
			$this->assertEmpty($x, sprintf('%s should be empty', $k));
		}

		$key_list = [
			'tz',
			'database',
			'openthc/app/origin',
			'openthc/bong/origin',
			// 'google_recaptcha_v2.public', // optional
			// 'google_recaptcha_v3.public'  // optional
			// 'google_recaptcha.secret'     // optional
		];

		foreach ($key_list as $k) {
			$x = \OpenTHC\Config::get($k);
			$this->assertNotEmpty($x, sprintf('%s is empty', $k));
		}

	}

}
