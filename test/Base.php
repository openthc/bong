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
			'base_uri' => $_ENV['OPENTHC_TEST_ORIGIN']
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
		$cfg['server'] = $_ENV['OPENTHC_TEST_ORIGIN'];
		$cfg['service-id'] = $_ENV['OPENTHC_TEST_CLIENT_SERVICE_ID'];
		$cfg['service-sk'] = $_ENV['OPENTHC_TEST_CLIENT_SERVICE_SK']; // v0
		$cfg['service-key'] = $_ENV['OPENTHC_TEST_CLIENT_SERVICE_SK']; // v1
		$cfg['contact'] = $_ENV['OPENTHC_TEST_CONTACT_ID'];
		$cfg['company'] = $_ENV['OPENTHC_TEST_COMPANY_ID'];
		$cfg['license'] = $_ENV['OPENTHC_TEST_LICENSE_ID'];
		// $cfg['license-id'] = $_ENV['OPENTHC_TEST_LICENSE_ID'];
		// $cfg['license-key'] = $_ENV['OPENTHC_TEST_LICENSE_SK'];

		$cre = \OpenTHC\CRE::factory($cfg);
		// $cre = new \OpenTHC\CRE\OpenTHC($cfg);
		$cre->setLicense($cfg['license']);

		return $cre;

	}

}
