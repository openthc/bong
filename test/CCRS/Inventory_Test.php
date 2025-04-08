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

	/**
	 *
	 */
	function test_delete_export() {

		// Create Object
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

		// Now Manually Advance Status to 202 (as if CCRS Export worked)
		$dbc = _dbc();
		$dbc->query('UPDATE inventory SET stat = 202 WHERE id = :i0', [ ':i0' => $obj['id'] ]);

		// Delete via API
		$res = $this->cre->inventory()->delete($obj['id']);
		// var_dump($res);
		$this->assertValidAPIResponse($res, 200);

		$chk = $dbc->fetchRow('SELECT * FROM inventory WHERE id = :i0', [ ':i0' => $obj['id'] ]);
		$this->assertIsArray($chk);
		$this->assertEquals(410, $chk['stat']);

		// Execute this Script
		require_once(APP_ROOT . '/bin/cre-ccrs-upload-inventory.php');
		$req_ulid = _cre_ccrs_upload_inventory([
			'--license' => OPENTHC_TEST_LICENSE_ID,
		]);
		$this->assertNotEmpty($req_ulid);

		// Should have a File in Log_Upload?
		$chk = $dbc->fetchRow('SELECT * FROM log_upload WHERE id = :a0', [ ':a0' => $req_ulid ]);
		$this->assertIsArray($chk);
		$this->assertEquals(100, $chk['stat']);

		// var_dump($chk);
		// Should have a Record in upload_object_process
		$chk = $dbc->fetchRow('SELECT * FROM upload_object_action WHERE upload_id = :u0 AND object_id = :o0', [
			':u0' => $req_ulid,
			':o0' => $obj['id']
		]);
		$this->assertEmpty($chk);
		// $this->assertIsArray($chk);
		// $this->assertEquals('DELETE', $chk['action']);

		// Now Pretend to Upload to CCRS and Get a Response?

		// Now Inventory Should be 410202
		$chk = $dbc->fetchRow('SELECT * FROM inventory WHERE id = :i0', [ ':i0' => $obj['id'] ]);
		$this->assertIsArray($chk);
		$this->assertEquals(410202, $chk['stat']);

		// Fake a Response?
		// What Response do we Get from CCRS?

	}

}
