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
		$env_list = [
			'OPENTHC_TEST_BASE',
			'OPENTHC_TEST_BASE_SERVICE_ID',
			'OPENTHC_TEST_BASE_SERVICE_SK',
			'OPENTHC_TEST_BIOTRACK_COMPANY',
			'OPENTHC_TEST_BIOTRACK_PASSWORD',
			'OPENTHC_TEST_BIOTRACK_USERNAME',
			'OPENTHC_TEST_CCRS_COMPANY_ID',
			'OPENTHC_TEST_CCRS_LICENSE_ID',
			'OPENTHC_TEST_LICENSE_KEY',
			'OPENTHC_TEST_METRC_LICENSE_KEY',
			'OPENTHC_TEST_METRC_SERVICE_KEY',
		];

		foreach ($env_list as $env) {
			$val = getenv($env);
			$this->assertNotEmpty($val, "$env is empty");
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
