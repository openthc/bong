<?php
/**
 * Test Lab Result Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class G_Lab_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->lab_sample()->create([
			'id' => _ulid(),
			'name' => sprintf('Test lab_sample CREATE %s', $this->_pid),
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
			'name' => sprintf('Test lab_sample DOUBLE', $this->_pid),
		];
		$res = $this->cre->lab_sample()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->lab_sample()->create($obj);
		$this->assertValidAPIResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->lab_sample()->search();
		$this->assertValidAPIResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test lab_sample UPDATE %s', $this->_pid),
		];
		$res = $this->cre->lab_sample()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->lab_sample()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test lab_sample DELETE %s', $this->_pid),
		];
		$res = $this->cre->lab_sample()->create($obj);

		$obj = $this->assertValidAPIResponse($res, 201);

		$res = $this->cre->lab_sample()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 200);

	}

}
