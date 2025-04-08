<?php
/**
 * Test Lab Result Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\CCRS;

class Lab_Report_Test extends \OpenTHC\Bong\Test\CCRS\Base_Case
{
	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->post('/lab/result', [
			'id' => _ulid(),
			'name' => sprintf('Test lab_sample CREATE %s', $this->_pid),
		]);
		$this->assertValidAPIResponse($res, 404);
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
		$res = $this->cre->post('/lab/result', $obj);
		$this->assertValidAPIResponse($res, 404);

		$res = $this->cre->post('/lab/result', $obj);
		$this->assertValidAPIResponse($res, 404);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->get('/lab/result');
		$this->assertValidAPIResponse($res, 404);
	}

	/**
	 *
	 */
	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test lab_sample UPDATE %s', $this->_pid),
		];
		$res = $this->cre->post('/lab/sample', $obj);
		$this->assertValidAPIResponse($res, 404);

		$res = $this->cre->post(sprintf('/lab/sample/%s', $obj['id']), $obj);
		$this->assertValidAPIResponse($res, 404);

	}

	/**
	 *
	 */
	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test lab_sample DELETE %s', $this->_pid),
		];
		$res = $this->cre->post('/lab/sample', $obj);
		$obj = $this->assertValidAPIResponse($res, 404);

		$res = $this->cre->delete(sprintf('/lab/sample/%s', $obj['id']));
		$this->assertValidAPIResponse($res, 404);

	}

}
