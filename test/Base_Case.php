<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test;

class Base_Case extends \PHPUnit\Framework\TestCase
{
	protected $_pid;
	protected $_tmp_file = '/tmp/bong-test-case.tmp';

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->_pid = getmypid();
	}

	/**
	 * Guzzle Client
	 */
	function _api(): object
	{
		$c = new \GuzzleHttp\Client(array(
			'base_uri' => getenv('OPENTHC_TEST_BASE'),
			'allow_redirects' => false,
			'debug' => $_ENV['debug-http'],
			'request.options' => array(
				'exceptions' => false,
			),
			'http_errors' => false,
			'cookies' => true,
		));

		return $c;
	}

	/**
	 *
	 */
	function getBONGtoCCRS()
	{
		$cfg = [];
		$cfg['id'] = 'openthc/bong';
		$cfg['cre'] = 'usa/wa/ccrs';
		$cfg['server'] = getenv('OPENTHC_TEST_BASE');
		$cfg['service-id'] = getenv('OPENTHC_TEST_BASE_SERVICE_ID');
		$cfg['service-sk'] = getenv('OPENTHC_TEST_BASE_SERVICE_KEY');
		$cfg['service-key'] = getenv('OPENTHC_TEST_BASE_SERVICE_KEY');
		$cfg['contact'] = getenv('OPENTHC_TEST_CONTACT_ID');
		$cfg['company'] = getenv('OPENTHC_TEST_COMPANY_ID');
		$cfg['license'] = getenv('OPENTHC_TEST_LICENSE_ID');
		// $cfg['license_id'] = getenv('OPENTHC_TEST_LICENSE_ID');
		// $cfg['license-key'] = getenv('OPENTHC_TEST_LICENSE_SECRET');

		// Always this one
		// $cfg = [
		// 	'id' => ''
		// ];
		// name = "Washington / CCRS"
		// class = "\OpenTHC\CRE\CCRS"
		// epoch = "2021-08-04"
		// engine = "openthc"
		// server = "https://bong.openthc.com/"
		// service = "bong"
		// service-id = "019KAGVX9MTGCQ96XZBRW6B3A3"
		// service-key = "019KAGVX9MTGCQ96XZBRW6B3A3"

		// $cfg = CRE::config('usa/wa/ccrs');
		// $cfg['company'] = $Company['id'];
		// $cfg['contact'] = '018NY6XC00C0NTACT000000000'; // get most recent sign-in?

		// $cre = \OpenTHC\CRE::factory($cfg);
		$cre = new \OpenTHC\CRE\OpenTHC($cfg);
		$cre->setLicense($cfg['license']);

		return $cre;

	}

}
