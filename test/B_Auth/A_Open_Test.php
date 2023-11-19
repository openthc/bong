<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\B_Auth;

class A_Open_Test extends \OpenTHC\Bong\Test\Base_Case
{
	/**
	 *
	 */
	function test_biotrack()
	{
		$api = $this->_api();
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => 'usa/nm',
			'company' => getenv('OPENTHC_TEST_BIOTRACK_COMPANY'),
			'username' => getenv('OPENTHC_TEST_BIOTRACK_USERNAME'),
			'password' => getenv('OPENTHC_TEST_BIOTRACK_PASSWORD'),
		]]);

		$code = $res->getStatusCode();
		$body = $res->getBody()->getContents();

		$this->assertEquals(200, $code);

	}

	/**
	 *
	 */
	function test_ccrs()
	{
		$api = $this->_api();
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => 'usa/wa',
			'service-id' => getenv('OPENTHC_TEST_BASE_SERVICE_ID'),
			'service-sk' => getenv('OPENTHC_TEST_BASE_SERVICE_SK'), // v1
			'service-key' => getenv('OPENTHC_TEST_BASE_SERVICE_SK'), // v0
			'company' => getenv('OPENTHC_TEST_CCRS_COMPANY_ID'),
			'license' => getenv('OPENTHC_TEST_CCRS_LICENSE_ID'),
			'license-key' => getenv('OPENTHC_TEST_LICENSE_KEY'),
		]]);

		$code = $res->getStatusCode();
		$body = $res->getBody()->getContents();

		$this->assertEquals(200, $code);

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
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => 'usa/co',
			'service-key' => getenv('OPENTHC_TEST_METRC_SERVICE_KEY'),
			'license-key' => getenv('OPENTHC_TEST_METRC_LICENSE_KEY'),
		]]);
		$code = $res->getStatusCode();
		$body = $res->getBody()->getContents();

		$this->assertEquals(200, $code);

	}

}
