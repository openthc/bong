<?php
/**
 * Cannafax Connection Test
 */

namespace OpenTHC\Bong\Test\Cannafax;

class Ping_Test extends \OpenTHC\Bong\Test\Base {

	/**
	 * @test
	 */
	function pingAuth() {

		$api = new \OpenTHC\Bong\CRE\Cannafax([
			'License' => [],
			'api_bearer_token' => \OpenTHC\Config::get('cre/cannafax/api_bearer_token'),
		]);

		$res = $api->ping();

		$this->assertIsArray($res);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['meta']['type']);
		$this->assertIsObject($res['data']);
		$res = $res['data'];

		$this->assertIsObject($res);
		$this->assertObjectHasProperty('status', $res);
		$this->assertEquals('success', $res->status);
		$this->assertObjectHasProperty('response', $res);

		$res = $res->response;
		$this->assertIsObject($res);
		$this->assertObjectHasProperty('status', $res);
		$this->assertEquals('Success', $res->status);
		$this->assertObjectHasProperty('message', $res);
		$this->assertEquals('Account is valid and connected.', $res->message);

	}

	/**
	 * @test
	 */
	function pingShouldFail() {

		$api = new \OpenTHC\Bong\CRE\Cannafax([
			'License' => [],
			'api_bearer_token' => '',
		]);

		$res = $api->ping();

		$this->assertIsArray($res);
		$this->assertEquals(200, $res['code']); // Should be 403?
		// $this->assertEquals(403, $res['code']);
		$this->assertEquals('application/json', $res['meta']['type']);
		$this->assertIsObject($res['data']);

		$res = $res['data'];
		$this->assertIsObject($res);
		$this->assertObjectHasProperty('status', $res);
		$this->assertEquals('success', $res->status);  // Should be "failure"?
		// $this->assertEquals('failure', $res->status);
		$this->assertObjectHasProperty('response', $res);

		$res = $res->response;
		$this->assertIsObject($res);
		$this->assertObjectHasProperty('status', $res);
		$this->assertEquals('error', $res->status);
		$this->assertObjectHasProperty('message', $res);
		$this->assertEquals('Unauthorized access', $res->message);

	}
}
