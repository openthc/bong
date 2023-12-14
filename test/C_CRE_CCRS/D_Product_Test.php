<?php
/**
 * Test Product Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class D_Product_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->product()->create([
			'id' => _ulid(),
			'name' => sprintf('Test product CREATE %s', $this->_pid),
			'type' => '018NY6XC00PTAF3TFBB51C8HX6',
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
			'name' => sprintf('Test product DOUBLE', $this->_pid),
			'type' => '018NY6XC00PTAF3TFBB51C8HX6',
		];
		$res = $this->cre->product()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->product()->create($obj);
		$this->assertValidResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->product()->search();
		$this->assertValidResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test product UPDATE %s', $this->_pid),
			'type' => '018NY6XC00PTAF3TFBB51C8HX6',
		];
		$res = $this->cre->product()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->product()->update($obj['id'], $obj);
		$this->assertValidResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test product DELETE %s', $this->_pid),
			'type' => '018NY6XC00PTAF3TFBB51C8HX6',
		];
		$res = $this->cre->product()->create($obj);

		$obj = $this->assertValidResponse($res, 201);

		$res = $this->cre->product()->delete($obj['id']);
		$this->assertValidResponse($res, 200);

	}

}
