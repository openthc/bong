<?php
/**
 * Test Inventory Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\CCRS;

class Inventory_Test extends \OpenTHC\Bong\Test\CCRS\Base_Case
{
	private $product_id = '010PENTHC09GQ455NJ9RG2WQ2T';

	private $section_id = '01HFMY1YWFMSTACZFB8HAQG99H';

	private $variety_id = '01HFMYNNPA14HKBNZA5NRS6K7E';

	/**
	 *
	 */
	function test_create()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test inventory CREATE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id, 'name' => 'TEST SECTION' ],
			'variety' => [ 'id' => $this->variety_id, 'name' => 'TEST VARIETY' ],
			'product' => [ 'id' => $this->product_id, 'name' => 'TEST PRODUCT' ],
		];
		$res = $this->cre->inventory()->create($obj);
		$this->assertValidAPIResponse($res, 201);
	}

	/**
	 *
	 */
	function test_create_duplicate()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test inventory DOUBLE', $this->_pid),
			'section' => [ 'id' => $this->section_id, 'name' => 'TEST SECTION' ],
			'variety' => [ 'id' => $this->variety_id, 'name' => 'TEST VARIETY' ],
			'product' => [ 'id' => $this->product_id, 'name' => 'TEST PRODUCT' ],
		];
		$res = $this->cre->inventory()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->inventory()->create($obj);
		$this->assertValidAPIResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->inventory()->search();
		$this->assertValidAPIResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test inventory UPDATE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id, 'name' => 'TEST SECTION' ],
			'variety' => [ 'id' => $this->variety_id, 'name' => 'TEST VARIETY' ],
			'product' => [ 'id' => $this->product_id, 'name' => 'TEST PRODUCT' ],
		];
		$res = $this->cre->inventory()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->inventory()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);

	}

	function test_delete()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Inventory DELETE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id, 'name' => 'TEST SECTION' ],
			'variety' => [ 'id' => $this->variety_id, 'name' => 'TEST VARIETY' ],
			'product' => [ 'id' => $this->product_id, 'name' => 'TEST PRODUCT' ],
		];
		$res = $this->cre->inventory()->create($obj);
		$this->assertValidAPIResponse($res, 201);
		$obj = $res['data'];

		$res = $this->cre->inventory()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 200);

		$res = $this->cre->inventory()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 410);

	}

}
