<?php
/**
 * Test Crop Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class E_Crop_Test extends \OpenTHC\Bong\Test\C_CRE_CCRS\Base_Case
{
	private $section_id = '01HFMY1YWFMSTACZFB8HAQG99H';

	private $variety_id = '01HFMYNNPA14HKBNZA5NRS6K7E';

	/**
	 *
	 */
	function test_create()
	{
		$res = $this->cre->crop()->create([
			'id' => _ulid(),
			'name' => sprintf('Test crop CREATE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
			'qty' => 1
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
			'name' => sprintf('Test crop DOUBLE', $this->_pid),
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
			'qty' => 1
		];
		$res = $this->cre->crop()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->crop()->create($obj);
		$this->assertValidAPIResponse($res, 409);

	}

	/**
	 *
	 */
	function test_search()
	{
		$res = $this->cre->crop()->search();
		$this->assertValidAPIResponse($res, 200);
	}

	function test_update()
	{
		$obj = [
			'id' => _ulid(),
			'name' => sprintf('Test crop UPDATE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
			'qty' => 1
		];
		$res = $this->cre->crop()->create($obj);
		$this->assertValidAPIResponse($res, 201);

		$res = $this->cre->crop()->update($obj['id'], $obj);
		$this->assertValidAPIResponse($res, 200);

	}

	function test_delete()
	{
		$obj0 = [
			'id' => _ulid(),
			'name' => sprintf('Test crop DELETE %s', $this->_pid),
			'section' => [ 'id' => $this->section_id ],
			'variety' => [ 'id' => $this->variety_id ],
		];
		$res = $this->cre->crop()->create($obj0);
		$res = $this->assertValidAPIResponse($res, 201);

		$res = $this->cre->crop()->delete($obj0['id']);
		$obj1 = $this->assertValidAPIResponse($res, 200);

		// $this->assertSame($obj0['id'], $obj1['id']);
		$this->assertEquals(410, $res['data']['stat']);

	}

}
