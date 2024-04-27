<?php
/**
 * Test Section Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class C_Section_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	function test_create()
	{
		$res = $this->cre->section()->create([
			'id' => _ulid(),
			'name' => sprintf('Test Section CREATE %s', $this->_pid),
		]);
		$this->assertValidAPIResponse($res, 201);
		$this->assertEquals(100, $res['data']['stat']);
	}

	function test_create_duplicate()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Section DOUBLE', $this->_pid),
		];
		$res = $this->cre->section()->create($obj);
		$this->assertValidAPIResponse($res, 201);
		$this->assertEquals(100, $res['data']['stat']);

		$res = $this->cre->section()->create($obj);
		$this->assertValidAPIResponse($res, 409);
		$this->assertEquals(409, $res['data']['stat']);
	}

	function test_search()
	{
		$res = $this->cre->section()->search();
		$this->assertValidAPIResponse($res, 200);
		$this->assertGreaterThan(2, count($res['data']));
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Section UPDATE %s', $this->_pid),
		];
		$res = $this->cre->section()->create($obj);
		$this->assertValidAPIResponse($res, 201);
		$this->assertEquals(100, $res['data']['stat']);

		$res = $this->cre->section()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);
		$this->assertEquals(100, $res['data']['stat']);
	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Section DELETE %s', $this->_pid),
		];
		$res = $this->cre->section()->create($obj);

		$obj1 = $this->assertValidAPIResponse($res, 201);

		$res = $this->cre->section()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 200);
		$this->assertEquals(410, $res['data']['stat']);
	}

	function test_update_before_create()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Section UPDATE_BEFORE_CREATE %s', $this->_pid),
		];
		$res = $this->cre->section()->update($obj['id'], $obj);
		// $this->assertValidAPIResponse($res, 201);
		$this->assertEquals(100, $res['data']['stat']);

		$res = $this->cre->section()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);

		// Now It Should be in the system with data object status 100
		$this->assertEquals(100, $res['data']['stat']);

		// Third Update
		$obj['name'] = sprintf('Section UPDATE_BEFORE_CREATE_RENAME %s', $this->_pid);
		$res = $this->cre->section()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);


		// Now Pretend We've Uploaded to CCRS and are waiting on response
		$dbc = _dbc();
		$License = $this->cre->getLicense();

		$dbc->query('UPDATE section SET stat = 102 WHERE license_id = :l0 AND id = :s0', [
			':l0' => $License['id'],
			':s0' => $obj['id'],
		]);

	}

	/**
	 * Create the Object; Wait for CCRS "Verification"
	 * Make sure that the next VERIFY is good and that UPDATE doesn't MODIFY
	 * Then MODIFY and make sure that UPDATE says 102
	 */
	function test_create_verify()
	{
		$dbc = _dbc();
		$License = $this->cre->getLicense();

		// Create
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Section CREATE_VERIFY %s', $this->_pid),
		];
		$res = $this->cre->section()->create($obj);
		$this->assertValidAPIResponse($res, 201);
		$this->assertEquals(100, $res['data']['stat']);

		// Now Pretend We've Uploaded to CCRS and are waiting on response
		$arg = [
			':l0' => $License['id'],
			':s0' => $obj['id'],
		];
		$dbc->query('UPDATE section SET stat = 102 WHERE license_id = :l0 AND id = :s0', $arg);

		// Now Call UPDATE with Same Info
		// Now It Should be in the system with data object status 102
		$res = $this->cre->section()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);
		$this->assertEquals(102, $res['data']['stat']);

		// CCRS Success
		$dbc->query('UPDATE section SET stat = 200 WHERE license_id = :l0 AND id = :s0', [
			':l0' => $License['id'],
			':s0' => $obj['id'],
		]);

		// Now Call UPDATE with Same Info
		// Now It Should be in the system with data object status 102
		$res = $this->cre->section()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);
		$this->assertEquals(200, $res['data']['stat']);

		// CCRS Verified
		$dbc->query('UPDATE section SET stat = 202 WHERE license_id = :l0 AND id = :s0', [
			':l0' => $License['id'],
			':s0' => $obj['id'],
		]);

		// Now Call UPDATE with Same Info
		// Now It Should be in the system with data object status 102
		$res = $this->cre->section()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 202);
		$this->assertEquals(202, $res['data']['stat']);

	}

}
