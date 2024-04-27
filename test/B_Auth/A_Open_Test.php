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
			'company' => OPENTHC_TEST_BIOTRACK_COMPANY,
			'username' => OPENTHC_TEST_BIOTRACK_USERNAME,
			'password' => OPENTHC_TEST_BIOTRACK_PASSWORD,
		]]);


		$this->assertValidResponse($res, 405, 'text/html');
		// $loc = $res->getHeaderLine('location');
		// var_dump($loc);

	}

	/**
	 *
	 */
	function test_ccrs()
	{
		$api = $this->_api();
		$res = $api->get('/auth/ping');
		$this->assertValidResponse($res, 200);
		// , [ 'form_params' => [
		// 	'cre' => 'usa/wa',
		// 	'service-id' => OPENTHC_TEST_CLIENT_SERVICE_ID,
		// 	'service-sk' => OPENTHC_TEST_CLIENT_SERVICE_SK, // v1
		// 	'company' => OPENTHC_TEST_CCRS_COMPANY_ID,
		// 	'license' => OPENTHC_TEST_CCRS_LICENSE_ID,
		// 	'license-key' => OPENTHC_TEST_LICENSE_KEY,
		// ]]);

		// $this->assertValidResponse($res, 302, 'text/html');
		// $loc = $res->getHeaderLine('location');
		// var_dump($loc);

	}

	// function test_leafdata()
	// {
	// 	$api = $this->_api();
	// 	$res = $api->post('/auth/open', [ 'form_params' => [
	// 		'cre' => 'usa/wa/ccrs',
	// 		'license' => $_ENV['leafdata-license'],
	// 		'license-key' => $_ENV['leafdata-license-key'],
	// 	]]);

	// 	$code = $res->getStatusCode();
	// 	$body = $res->getBody()->getContents();

	// 	$this->assertEquals(200, $code);

	// }

	function test_metrc()
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
