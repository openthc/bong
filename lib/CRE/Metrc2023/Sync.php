<?php
/**
 * Metrc 2023
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\Metrc2023;

class Sync
{
	private $_cre;

	private $_dbc;

	/**
	 *
	 */
	function __construct($cre, $dbc)
	{
		$this->_cre = $cre;
		$this->_dbc = $dbc;

		$this->_License = $this->_cre->getLicense();
		// $this->_auth_token = $cfg['api_bearer_token'];
	}

	function execute()
	{
		$ret = [];

		// $req = $this->_cre->_curl_init('/unitsofmeasure/v2/active');
		// $res = $this->_cre->_curl_exec($req);
		// var_dump($res);

		// $req = $this->_cre->_curl_init('/wastemethods/v2/');
		// $res = $this->_cre->_curl_exec($req);
		// var_dump($res);

		// /packages/v2/types
		// /packages/v2/adjust/reasons

		$ret['contact'] = $this->contact();
		$ret['section'] = $this->section();
		$ret['variety'] = $this->variety();
		// /items/v2/categories
		// /items/v2/brands
		$ret['product'] = $this->product();
		$ret['inventory'] = $this->inventory();
		$ret['crop'] = $this->crop();
		$ret['crop_collect'] = $this->crop_collect();
		// $this->lab();
		// $this->b2b();
		// $this->b2c();

		return $ret;
	}

	function contact()
	{
		$res = $this->_cre->contact()->search();
		foreach ($res['data'] as $rec) {

			$hid = sodium_crypto_generichash($rec['FullName'], '', 16);

			// $rec['id'] = OID128();
			$rec['Id'] = md5($rec['FullName']);
			// var_dump($rec); exit;

			$obj_hash = \OpenTHC\CRE\Base::objHash($rec);

			$chk = $this->_dbc->fetchRow('SELECT id, hash FROM contact WHERE license_id = :l0 AND name = :n1', [
				':l0' => $this->_License['id'],
				':n1' => $rec['FullName'],
			]);
			if (empty($chk['id'])) {
				$insert_count++;
				$this->_dbc->insert('contact', [
					'id' => $rec['Id'],
					'license_id' => $this->_License['id'],
					'stat' => 200,
					'name' => $rec['FullName'],
					'data' => json_encode($rec),
					'hash' => $obj_hash,
				]);
			} else {
				$update_count++;
				$update = [
					'stat' => 200,
					'name' => $rec['FullName'],
					'data' => json_encode($rec),
					'hash' => $obj_hash,
				];
				$this->_dbc->update('contact', $update, [ 'id' => $chk['id'] ]);
			}
		}

		return [
			'insert' => $insert_count,
			'update' => $update_count,
		];

	}

	function crop()
	{
		$res = $this->_cre->crop()->search();
		// var_dump($res);
		foreach ($res['data'] as $rec) {

			$obj_hash = \OpenTHC\CRE\Base::objHash($rec);

			$chk = $this->_dbc->fetchRow('SELECT id, hash FROM crop WHERE license_id = :l0 AND id = :o1', [
				':l0' => $this->_License['id'],
				':o1' => $rec['Id'],
			]);
			if (empty($chk['id'])) {


			}
		}

		return [
			'insert' => $insert_count,
			'update' => $update_count,
		];

	}

	function crop_collect()
	{
		$url = $this->_cre->_make_url('/harvests/v2/active');
		$req = $this->_cre->_curl_init($url);
		$res = $this->_cre->_curl_exec($req);
		// var_dump($res);
	}

	function inventory()
	{
		$insert_count = 0;
		$update_count = 0;

		$res = $this->_cre->inventory()->search();
		foreach ($res['data'] as $rec) {

			$obj_hash = \OpenTHC\CRE\Base::objHash($rec);

			$chk = $this->_dbc->fetchRow('SELECT id, hash FROM inventory WHERE license_id = :l0 AND id = :o1', [
				':l0' => $this->_License['id'],
				':o1' => $rec['Id'],
			]);
			if (empty($chk['id'])) {


			}
		}

		// Immature Plants, Seeds, Clones?
		$url = 'plantbatches/v2/active';
		$url = $this->_cre->_make_url($url);
		$req = $this->_cre->_curl_init($url);
		$res = $this->_cre->_curl_exec($req);
		switch ($res['code']) {
		case 200:
			foreach ($res['data']['Data'] as $rec) {

				var_dump($rec); exit;

				$obj_hash = \OpenTHC\CRE\Base::objHash($rec);

				$chk = $this->_dbc->fetchRow('SELECT id, hash FROM inventory WHERE license_id = :l0 AND id = :o1', [
					':l0' => $this->_License['id'],
					':o1' => $rec['Id'],
				]);
				if (empty($chk['id'])) {

				}

			}
		}

		return [
			'insert' => $insert_count,
			'update' => $update_count,
		];

	}

	function product()
	{
		$insert_count = 0;
		$update_count = 0;

		$res = $this->_cre->product()->search();
		foreach ($res['data'] as $rec) {

			$obj_hash = \OpenTHC\CRE\Base::objHash($rec);

			$chk = $this->_dbc->fetchRow('SELECT id, hash FROM product WHERE license_id = :l0 AND id = :o1', [
				':l0' => $this->_License['id'],
				':o1' => $rec['Id'],
			]);
			if (empty($chk['id'])) {
				// Insert
				$insert_count++;
				$this->_dbc->insert('product', [
					'id' => $rec['Id'],
					'license_id' => $this->_License['id'],
					'stat' => 200,
					'name' => $rec['Name'],
					'data' => json_encode($rec),
					'hash' => $obj_hash,
				]);
			} else {
				// $this->_dbc->update()
				$update_count++;
			}
		}

		return [
			'insert' => $insert_count,
			'update' => $update_count,
		];

	}

	function section()
	{
		$insert_count = 0;
		$update_count = 0;

		$res = $this->_cre->section()->search();
		// var_dump($res);
		foreach ($res['data'] as $rec) {

			$obj_hash = \OpenTHC\CRE\Base::objHash($rec);

			$chk = $this->_dbc->fetchRow('SELECT id, hash FROM section WHERE license_id = :l0 AND id = :o1', [
				':l0' => $this->_License['id'],
				':o1' => $rec['Id'],
			]);
			if (empty($chk['id'])) {
				$insert_count++;
				$this->_dbc->insert('section', [
					'id' => $rec['Id'],
					'license_id' => $this->_License['id'],
					'stat' => 200,
					'name' => $rec['Name'],
					'data' => json_encode($rec),
					'hash' => $obj_hash,
				]);
			} else {
				$update_count++;
				$update = [
					'stat' => 200,
					'name' => $rec['Name'],
					'data' => json_encode($rec),
					'hash' => $obj_hash,
				];
				$this->_dbc->update('section', $update, [ 'id' => $chk['id'] ]);
			}
		}

		return [
			'insert' => $insert_count,
			'update' => $update_count,
		];
	}

	function variety()
	{
		$insert_count = 0;
		$update_count = 0;

		$res = $this->_cre->variety()->search();
		foreach ($res['data'] as $rec) {

			$obj_hash = \OpenTHC\CRE\Base::objHash($rec);

			$chk = $this->_dbc->fetchRow('SELECT id, hash FROM variety WHERE license_id = :l0 AND id = :o1', [
				':l0' => $this->_License['id'],
				':o1' => $rec['Id'],
			]);
			if (empty($chk['id'])) {
				$insert_count++;
				$this->_dbc->insert('variety', [
					'id' => $rec['Id'],
					'license_id' => $this->_License['id'],
					'stat' => 200,
					'name' => $rec['Name'],
					'data' => json_encode($rec),
					'hash' => $obj_hash,
				]);
			} else {
				$update_count++;
				$update = [
					'stat' => 200,
					'name' => $rec['Name'],
					'data' => json_encode($rec),
					'hash' => $obj_hash,
				];
				$this->_dbc->update('variety', $update, [ 'id' => $chk['id'] ]);
			}
		}

		return [
			'insert' => $insert_count,
			'update' => $update_count,
		];
	}

}
