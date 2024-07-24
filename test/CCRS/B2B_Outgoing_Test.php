<?php
/**
 * Test B2B Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\CCRS;

class B2B_Outgoing_Test extends \OpenTHC\Bong\Test\CCRS\Base_Case
{
	/**
	 * @test
	 */
	function create()
	{
		// $this->expectException('\Exception');

		// try {
			// API throws Exception on 501
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

			$this->assertValidAPIResponse($res, 405);
			// $this->assertIsArray($res['meta']);
			// $this->assertArrayKeyExists('note', $res['meta']);
			// var_dump($res);

		// } catch (\Exception $e) {

		// 	$this->assertEquals('Invalid Response Code: 501 from OpenTHC [LRO-208]', $e->getMessage());

		// 	$cre_mirror = new \ReflectionClass($this->cre);
		// 	$cre_mirror_res_code = $cre_mirror->getProperty('_res_code');
		// 	$cre_mirror_res_code->setAccessible(true);
		// 	$this->assertEquals(501, $cre_mirror_res_code->getValue($this->cre));
		// }

	}

	/**
	 *
	 */
	function create_duplicate() {

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
		$this->assertValidAPIResponse($res, 501);

		$res = $this->cre->b2b()->create($b2b);
		$this->assertValidAPIResponse($res, 501);

	}

	/**
	 * @test
	 */
	function search() {

		$res = $this->cre->b2b()->outgoing()->search();
		$this->assertValidAPIResponse($res, 200);

	}

	/**
	 * @test
	 */
	function update() {

		$b2b = [
			'id' => _ulid(),
			'type' => 'outgoing',
			'source' => [
				'id' => '010PENTHC0DEM0L1CENSE0000A',
			],
			'target' => [
				'id' => '010PENTHC0DEM0L1CENSE0000B',
				'phone' => '+12025551212',
				'email' => 'test@example.com',
			],
			'item_list' => [
				'id' => _ulid(),
				'inventory' => [
					'id' => _ulid(),
				],
				'unit_count' => 10,
				'unit_price' => 10,
			]
		];

		// $res = $this->cre->b2b()->create($b2b);
		// $this->assertValidAPIResponse($res, 501);

		$res = $this->cre->b2b()->update($b2b['id'], $b2b);
		$this->assertValidAPIResponse($res, 200);

		return $b2b;

	}

	/**
	 * @test
	 * @depends update
	 */
	function delete($b2b)
	{
		$res = $this->cre->b2b()->outgoing()->delete($b2b['id']);
		$this->assertValidAPIResponse($res, 405);
	}

}
