<?php
/**
 * Test Variety Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class B_Variety_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->variety()->create([
			'id' => _ulid(),
			'name' => sprintf('Test Variety CREATE %s', $this->_pid),
		]);
		$this->assertValidResponse($res, 201);
	}

	/**
	 *
	 */
	function test_create_duplicate()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Variety DOUBLE', $this->_pid),
		];
		$res = $this->cre->variety()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->variety()->create($obj);
		$this->assertValidResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->variety()->search();
		$this->assertValidResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Variety UPDATE %s', $this->_pid),
		];
		$res = $this->cre->variety()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->variety()->update($obj['id'], $obj);
		$this->assertValidResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Variety DELETE %s', $this->_pid),
		];
		$res = $this->cre->variety()->create($obj);

		$obj1 = $this->assertValidResponse($res, 201);

		// $res = $this->cre->variety()->delete($obj['id']);
		$res = $this->cre->variety()->delete($obj['name']);
		$this->assertValidResponse($res, 200);

	}

}
