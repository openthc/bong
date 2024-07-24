<?php
/**
 * Test B2C Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\CCRS;

class B2B_Incoming_Test extends \OpenTHC\Bong\Test\CCRS\Base_Case
{
	/**
	 * @test
	 */
	function create() {

		// https://backendtea.com/post/phpunit-exception-test/
		// $this->expectException('\Exception');

		// try {
			// API throws Exception on 501
			$res = $this->cre->b2b()->create([
				'id' => _ulid(),
				'type' => 'incoming',
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
	 * @test
	 */
	function search() {

		$res = $this->cre->b2b()->incoming()->search();
		$this->assertValidAPIResponse($res, 200);

	}

	/**
	 * @test
	 */
	function update_v0()
	{
		$b2b = [
			'id' => _ulid(),
			'type' => 'incoming',
			'source' => [
				'id' => '010PENTHC0DEM0L1CENSE0000B',
			],
			'target' => [
				'id' => '010PENTHC0DEM0L1CENSE0000A',
				'phone' => '+12025551212',
				'email' => 'test@example.com',
			],
			'item_list' => [
				[
					'id' => _ulid(),
					'inventory' => [
						'id' => _ulid(),
					],
					'unit_count' => 11,
					'unit_price' => 11,
				],
				[
					'id' => _ulid(),
					'inventory' => [
						'id' => _ulid(),
					],
					'unit_count' => 12,
					'unit_price' => 12,
				]

			]
		];

		$res = $this->cre->b2b()->update($b2b['id'], $b2b);
		$res = $this->assertValidAPIResponse($res);

	}

	/**
	 * @test
	 */
	function update_v1()
	{
		$b2b = [
			'id' => _ulid(),
			'type' => 'incoming',
			'source' => [
				'id' => '010PENTHC0DEM0L1CENSE0000B',
			],
			'target' => [
				'id' => '010PENTHC0DEM0L1CENSE0000A',
				'phone' => '+12025551212',
				'email' => 'test@example.com',
			],
			'item_list' => [
				[
					'id' => _ulid(),
					'inventory' => [
						'id' => _ulid(),
					],
					'unit_count' => 11,
					'unit_price' => 11,
				],
				[
					'id' => _ulid(),
					'inventory' => [
						'id' => _ulid(),
					],
					'unit_count' => 12,
					'unit_price' => 12,
				]

			]
		];

		$res = $this->cre->b2b()->incoming()->update($b2b['id'], $b2b);
		$res = $this->assertValidAPIResponse($res);

		return $b2b;

	}

	/**
	 * @test
	 * @depends update_v1
	 */
	function delete($b2b)
	{
		$res = $this->cre->b2b()->incoming()->delete($b2b['id']);
		$this->assertValidAPIResponse($res, 405);

	}

}
