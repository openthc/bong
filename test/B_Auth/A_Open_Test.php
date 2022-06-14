<?php
/**
 */

namespace OpenTHC\Bong\Test\B_Auth;

class A_Open_Test extends \Test\Base_Case
{
	function test_biotrack()
	{
		$api = $this->_api();
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => 'usa/nm',
			'username' => $_ENV['biotrack-username'],
			'password' => $_ENV['biotrack-password'],
		]]);

		$code = $res->getStatusCode();
		$body = $res->getBody()->getContents();


	}

	function test_leafdata()
	{
		$api = $this->_api();
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => 'usa/wa/test',
			'license' => $_ENV['leafdata-license'],
			'license-key' => $_ENV['leafdata-license-key'],
		]]);

		$code = $res->getStatusCode();
		$body = $res->getBody()->getContents();

	}

	function test_metrc()
	{
		$api = $this->_api();
		$res = $api->post('/auth/open', [ 'form_params' => [
			'cre' => 'usa/ok/test',
			'service-key' => $_ENV['metrc-service'],
			'license-key' => $_ENV['metrc-license'],
		]]);

		$code = $res->getStatusCode();
		$body = $res->getBody()->getContents();

	}

}
