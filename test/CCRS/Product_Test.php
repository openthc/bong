<?php
/**
 * Test Product Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\CCRS;

class Product_Test extends \OpenTHC\Bong\Test\CCRS\Base_Case
{
	/**
	 *
	 */
	function test_create()
	{
		$obj0 = [
			'id' => _ulid(),
			'name' => sprintf('Test product CREATE %s', $this->_pid),
			'type' => '018NY6XC00PTAF3TFBB51C8HX6',
		];

		$res = $this->cre->product()->create($obj0);
		$res = $this->assertValidAPIResponse($res, 201);

		$obj1 = $res['data'];

		$this->assertSame($obj0['id'], $obj1['id']);

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
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->product()->create($obj);
		$this->assertValidAPIResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->product()->search();
		$this->assertValidAPIResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test product UPDATE %s', $this->_pid),
			'type' => '018NY6XC00PTAF3TFBB51C8HX6',
		];
		$res = $this->cre->product()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->product()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test product DELETE %s', $this->_pid),
			'type' => '018NY6XC00PTAF3TFBB51C8HX6',
		];
		$res = $this->cre->product()->create($obj);

		$res = $this->assertValidAPIResponse($res, 201);

		$res = $this->cre->product()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 200);

	}

}
