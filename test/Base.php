<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test;

class Base extends \OpenTHC\Test\Base
{
	protected $_tmp_file = '/tmp/bong-test-case.tmp';

	/**
	 * Guzzle Client
	 */
	function _api(): object
	{
		$c = $this->getGuzzleClient([
			'base_uri' => OPENTHC_TEST_ORIGIN
		]);
		return $c;
	}

	/**
	 *
	 */
	function getBONGtoCCRS()
	{
		// $cfg = CRE::config('usa/wa');

		$cfg = [];
		$cfg['id'] = 'usa/wa';
		$cfg['code'] = 'usa/wa';
		$cfg['cre'] = 'usa/wa';
		$cfg['server'] = OPENTHC_TEST_ORIGIN;
		$cfg['service-id'] = OPENTHC_TEST_CLIENT_SERVICE_ID;
		$cfg['service-sk'] = OPENTHC_TEST_CLIENT_SERVICE_SK; // v0
		$cfg['service-key'] = OPENTHC_TEST_CLIENT_SERVICE_SK; // v1
		$cfg['contact'] = OPENTHC_TEST_CONTACT_ID;
		$cfg['company'] = OPENTHC_TEST_COMPANY_ID;
		$cfg['license'] = OPENTHC_TEST_LICENSE_ID;
		// $cfg['license-id'] = OPENTHC_TEST_LICENSE_ID;
		// $cfg['license-key'] = OPENTHC_TEST_LICENSE_SECRET;

		$cre = \OpenTHC\CRE::factory($cfg);
		// $cre = new \OpenTHC\CRE\OpenTHC($cfg);
		$cre->setLicense($cfg['license']);

		return $cre;

	}

}
