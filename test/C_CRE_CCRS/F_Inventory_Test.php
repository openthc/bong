<?php
/**
 * Test Inventory Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class F_Inventory_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->inventory()->create([
			'id' => _ulid(),
			'name' => sprintf('Test inventory CREATE %s', $this->_pid),
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
			'name' => sprintf('Test inventory DOUBLE', $this->_pid),
		];
		$res = $this->cre->inventory()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->inventory()->create($obj);
		$this->assertValidResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->inventory()->search();
		$this->assertValidResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test inventory UPDATE %s', $this->_pid),
		];
		$res = $this->cre->inventory()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->inventory()->update($obj['id'], $obj);
		$this->assertValidResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test inventory DELETE %s', $this->_pid),
		];
		$res = $this->cre->inventory()->create($obj);

		$obj = $this->assertValidResponse($res, 201);

		$res = $this->cre->inventory()->delete($obj['id']);
		$this->assertValidResponse($res, 200);

	}

}
