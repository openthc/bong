<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\Auth;

class Open_Test extends \OpenTHC\Bong\Test\Base
{
	/**
	 *
	 */
	function test_biotrack()
	{
		$chk = $_ENV['OPENTHC_TEST_BIOTRACK_SERVICE'];
		if (empty($chk)) {
			$this->markTestSkipped('No BioTrack Configuration');
		}

		$api = $this->_api();
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => $_ENV['OPENTHC_TEST_BIOTRACK_SERVICE'],
			'company' => $_ENV['OPENTHC_TEST_BIOTRACK_COMPANY'],
			'username' => $_ENV['OPENTHC_TEST_BIOTRACK_USERNAME'],
			'password' => $_ENV['OPENTHC_TEST_BIOTRACK_PASSWORD'],
		]]);

		$this->assertValidResponse($res, 200);

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
		// 	'license-key' => $_ENV['OPENTHC_TEST_LICENSE_SK'],
		// ]]);

		// $this->assertValidResponse($res, 302, 'text/html');
		// $loc = $res->getHeaderLine('location');
		// var_dump($loc);

	}

	function test_metrc()
	{
		$chk = $_ENV['OPENTHC_TEST_METRC_SERVICE'];
		if (empty($chk)) {
			$this->markTestSkipped('No Metrc Configuration');
		}

		$api = $this->_api();
		$res = $api->get('/auth/ping');
		$this->assertValidResponse($res, 200);

		$cfg = [];
		// $cre = \OpenTHC\CRE::factory($cfg);
		// $cre = new \OpenTHC\CRE\OpenTHC($cfg);
		// $cre->setLicense($cfg['license']);


	}

}
