<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\Unit;

class Config_Test extends \OpenTHC\Bong\Test\Base
{
	function test_env()
	{
		$key_list = [
			'OPENTHC_TEST_ORIGIN',
			'OPENTHC_TEST_CLIENT_SERVICE_ID',
			'OPENTHC_TEST_CLIENT_SERVICE_SK',
			'OPENTHC_TEST_LICENSE_CODE',
			'OPENTHC_TEST_LICENSE_SK',
			'OPENTHC_TEST_BIOTRACK_COMPANY',
			'OPENTHC_TEST_BIOTRACK_PASSWORD',
			'OPENTHC_TEST_BIOTRACK_USERNAME',
			'OPENTHC_TEST_CCRS_COMPANY_ID',
			'OPENTHC_TEST_CCRS_LICENSE_ID',
		];

		foreach ($key_list as $key) {
			$this->assertArrayHasKey($key, $_ENV);
			$this->assertNotEmpty($_ENV[$key], sprintf('$_ENV missing "%s"', $key));
		}

	}

	/**
	 * @test
	 */
	function all_config()
	{
		// We Want these to come from the configuration YAML
		// But for now they are in the \OpenTHC\Config::
		$key_list = [
			'cre/usa/wa/ccrs/tz',
			'cre/usa/wa/ccrs/username',
			'cre/usa/wa/ccrs/password',
			'cre/usa/wa/ccrs/service-key',
		];
		foreach ($key_list as $k) {
			$x = \OpenTHC\Config::get($k);
			$this->assertNotEmpty($x, sprintf('%s should NOT be empty', $k));
			// $this->assertEmpty($x, sprintf('%s should be empty', $k));
		}

		// $cfg = \OpenTHC\CRE::getClient('usa/wa');
		// $cfg = \OpenTHC\CRE::getConfig('usa/wa');

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
