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
		$this->assertValidAPIResponse($res, 201);

	}

	/**
	 *
	 */
	function test_create_duplicate()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Variety DOUBLE %s', $this->_pid),
		];
		$res = $this->cre->variety()->create($obj);
		$this->assertValidAPIResponse($res, 201);
		$id0 = $res['data']['id'];

		$res = $this->cre->variety()->create($obj);
		$this->assertValidAPIResponse($res, 409);
		$id1 = $res['data']['id'];

		$this->assertSame($id0, $id1);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->variety()->search();
		$this->assertValidAPIResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Variety UPDATE %s', $this->_pid),
		];
		$res = $this->cre->variety()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->variety()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test Variety DELETE %s', $this->_pid),
		];
		$res = $this->cre->variety()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$obj = $res['data'];

		$res = $this->cre->variety()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 200);

		$res = $this->cre->variety()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 410);

	}

}
