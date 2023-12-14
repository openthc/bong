<?php
/**
 * Test Crop Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class E_Crop_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->crop()->create([
			'id' => _ulid(),
			'name' => sprintf('Test crop CREATE %s', $this->_pid),
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
			'name' => sprintf('Test crop DOUBLE', $this->_pid),
		];
		$res = $this->cre->crop()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->crop()->create($obj);
		$this->assertValidResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->crop()->search();
		$this->assertValidResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test crop UPDATE %s', $this->_pid),
		];
		$res = $this->cre->crop()->create($obj);
		$this->assertValidResponse($res, 201);

		$res = $this->cre->crop()->update($obj['id'], $obj);
		$this->assertValidResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test crop DELETE %s', $this->_pid),
		];
		$res = $this->cre->crop()->create($obj);

		$obj = $this->assertValidResponse($res, 201);

		$res = $this->cre->crop()->delete($obj['id']);
		$this->assertValidResponse($res, 200);

	}

}
