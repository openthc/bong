<?php
/**
 * Test B2B Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class H_B2B_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	function test_create()
	{
		$res = $this->cre->b2b()->create([
			'id' => _ulid(),
			'type' => 'outgoing',
			'item_list' => [
				'id' => _ulid(),
				'inventory' => [
					'id' => _ulid(),
				],
				'unit_count' => 10,
				'unit_price' => 10,
			]
		]);

		$this->assertValidAPIResponse($res, 201);

	}

	function test_create_duplicate()
	{
		$b2b = [
			'id' => _ulid(),
			'type' => 'outgoing',
			'item_list' => [
				'id' => _ulid(),
				'inventory' => [
					'id' => _ulid(),
				],
				'unit_count' => 10,
				'unit_price' => 10,
			]
		];

		$res = $this->cre->b2b()->create($b2b);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->b2b()->create($b2b);
		$this->assertValidAPIResponse($res, 409);

	}

	function test_search()
	{
		$res = $cre->b2b()->search();
		$this->assertValidAPIResponse($res, 200);
	}

	function test_update()
	{
		$b2b = [
			'id' => _ulid(),
			'type' => 'outgoing',
			'item_list' => [
				'id' => _ulid(),
				'inventory' => [
					'id' => _ulid(),
				],
				'unit_count' => 10,
				'unit_price' => 10,
			]
		];

		$res = $this->cre->b2b()->create($b2b);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->b2b()->update($b2b);
		$this->assertValidAPIResponse($res, 409);

		return $b2b;

	}

	/**
	 * @depends test_update
	 */
	function test_delete($b2b)
	{
		$res = $this->cre->b2b()->delete($b2b['id']);
		$this->assertValidAPIResponse($res);

	}

}
