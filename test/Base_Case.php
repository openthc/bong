<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test;

class Base_Case extends \OpenTHC\Test\Base_Case
{
	protected $_tmp_file = '/tmp/bong-test-case.tmp';

	/**
	 * Guzzle Client
	 */
	function _api(): object
	{
		$c = $this->getGuzzleClient(getenv('OPENTHC_TEST_BASE'));
		return $c;
	}

	function assertValidResponse($res, $want_code)
	{
		$this->assertNotEmpty($res);
		$this->assertIsArray($res);
		$this->assertArrayHasKey('code', $res);
		$this->assertArrayHasKey('data', $res);
		$this->assertArrayHasKey('meta', $res);

		$this->assertEquals($want_code, $res['code']);

		return $res;
	}

	/**
	 *
	 */
	function getBONGtoCCRS()
	{
		// $cfg = CRE::config('usa/wa');

		$cfg = [];
		$cfg['id'] = 'openthc/bong';
		$cfg['cre'] = 'usa/wa';
		$cfg['server'] = getenv('OPENTHC_TEST_BASE');
		$cfg['service-id'] = getenv('OPENTHC_TEST_BASE_SERVICE_ID');
		$cfg['service-sk'] = getenv('OPENTHC_TEST_BASE_SERVICE_SK'); // v1
		$cfg['service-key'] = getenv('OPENTHC_TEST_BASE_SERVICE_SK'); // v0
		$cfg['contact'] = getenv('OPENTHC_TEST_CONTACT_ID');
		$cfg['company'] = getenv('OPENTHC_TEST_COMPANY_ID');
		$cfg['license'] = getenv('OPENTHC_TEST_LICENSE_ID');
		// $cfg['license_id'] = getenv('OPENTHC_TEST_LICENSE_ID');
		// $cfg['license-key'] = getenv('OPENTHC_TEST_LICENSE_SECRET');

		// $cre = \OpenTHC\CRE::factory($cfg);
		$cre = new \OpenTHC\CRE\OpenTHC($cfg);
		$cre->setLicense($cfg['license']);

		return $cre;

	}

}
