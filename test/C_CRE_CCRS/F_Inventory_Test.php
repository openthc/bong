<?php
/**
 * Test Inventory Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class F_Inventory_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	private $product_id = '010PENTHC09GQ455NJ9RG2WQ2T';

	private $section_id = '01HFMY1YWFMSTACZFB8HAQG99H';

	private $variety_id = '01HFMYNNPA14HKBNZA5NRS6K7E';

	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->inventory()->create([
			'id' => _ulid(),
			'name' => sprintf('Test inventory CREATE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
			'product' => [ 'id' => $this->product_id ],
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
			'name' => sprintf('Test inventory DOUBLE', $this->_pid),
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
			'product' => [ 'id' => $this->product_id ],
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
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
			'product' => [ 'id' => $this->product_id ],
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
			'name' => sprintf('Test inventory DELETE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
			'product' => [ 'id' => $this->product_id ],
		];
		$res = $this->cre->inventory()->create($obj);

		$obj = $this->assertValidAPIResponse($res, 201);

		$res = $this->cre->inventory()->delete($obj['id']);
		$this->assertValidAPIResponse($res, 200);

	}

}
