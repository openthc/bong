<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\B_Auth;

class A_Open_Test extends \OpenTHC\Bong\Test\Base
{
	/**
	 *
	 */
	function test_biotrack()
	{
		$api = $this->_api();
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => 'usa/nm',
			'company' => $_ENV['OPENTHC_TEST_BIOTRACK_COMPANY'],
			'username' => $_ENV['OPENTHC_TEST_BIOTRACK_USERNAME'],
			'password' => $_ENV['OPENTHC_TEST_BIOTRACK_PASSWORD'],
		]]);


		$this->assertValidResponse($res, 405);
		// $loc = $res->getHeaderLine('location');
		// var_dump($loc);

	}

	/**
	 *
	 */
	function test_ccrs()
	{
		// $api = $this->_api();
		$api = $this->getBONGtoCCRS();
		$res = $api->get('/auth/ping');
		$this->assertValidAPIResponse($res, 200);
		// , [ 'form_params' => [
		// 	'cre' => 'usa/wa',
		// 	'service-id' => $_ENV['OPENTHC_TEST_CLIENT_SERVICE_ID'],
		// 	'service-sk' => $_ENV['OPENTHC_TEST_CLIENT_SERVICE_SK'], // v1
		// 	'company' => $_ENV['OPENTHC_TEST_CCRS_COMPANY_ID'],
		// 	'license' => $_ENV['OPENTHC_TEST_CCRS_LICENSE_ID'],
		// 	'license-key' => $_ENV['OPENTHC_TEST_LICENSE_KEY'],
		// ]]);

		// $this->assertValidResponse($res, 302, 'text/html');
		// $loc = $res->getHeaderLine('location');
		// var_dump($loc);

	}

	function x_test_metrc()
	{
		$api = $this->_api();
		$res = $api->get('/auth/ping');
		$this->assertValidResponse($res, 200);

		$cfg = [];
		// $cre = \OpenTHC\CRE::factory($cfg);
		// $cre = new \OpenTHC\CRE\OpenTHC($cfg);
		// $cre->setLicense($cfg['license']);


	}

}
