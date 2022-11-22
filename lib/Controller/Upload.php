<?php
/**
 * Upload Controller
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller;

use OpenTHC\CRE;

class Upload extends \OpenTHC\Controller\Base
{
	/**
	 * Handle the POST/Upload of an INCOMING type file
	 */
	function incoming($REQ, $RES, $ARG)
	{
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'POST':

				$type = $_SERVER['CONTENT_TYPE']; // text/csv
				switch ($type) {
					case 'text/csv':

						$size = $_SERVER['CONTENT_LENGTH'];

						$source_name = $_SERVER['HTTP_CONTENT_NAME'];

						$output_data = file_get_contents('php://input');
						$output_file = sprintf('%s/var/ccrs-incoming/%s', APP_ROOT, $source_name);

						$output_size = file_put_contents($output_file, $output_data);

						__exit_text("Uploaded $output_file is $output_size bytes\n");

						break;

					case 'text/email':

						$size = $_SERVER['CONTENT_LENGTH'];

						$output_data = file_get_contents('php://input');
						$output_name = sha1($output_data);
						$output_file = sprintf('%s/var/ccrs-incoming-mail/%s', APP_ROOT, $output_name);

						$output_size = file_put_contents($output_file, $output_data);

						__exit_text("Uploaded $output_file is $output_size bytes\n");

				}

		}

		__exit_text('Not Implemented', 501);

	}

	/**
	 *
	 */
	function outgoing($REQ, $RES, $ARG)
	{

		$type = $_SERVER['CONTENT_TYPE']; // text/csv
		$size = $_SERVER['CONTENT_LENGTH'];

		$source_name = $_SERVER['HTTP_CONTENT_NAME'];
		$source_type = strtolower(preg_match('/^([a-z]+)_/i', $source_name, $m) ? $m[1] : '');

		$output_data = file_get_contents('php://input');
		$output_file = sprintf('%s/var/ccrs-outgoing/%s', APP_ROOT, $source_name);
		$rec_count = 0;

		// Lookup Code?
		$dbc_bong = _dbc();

		$License = [
			'id' => $_SERVER['HTTP_OPENTHC_LICENSE'],
			'code' => $_SERVER['HTTP_OPENTHC_LICENSE_CODE'],
			'name' => $_SERVER['HTTP_OPENTHC_LICENSE_NAME'],
		];

		if (empty($License['id'])) {
			return $RES->withJSON([
				'data' => [],
				'meta' => [ 'detail' => 'Invalid Request [LCU-068]' ]
			], 400);
		}

		if (empty($License['code'])) {
			return $RES->withJSON([
				'data' => [],
				'meta' => [ 'detail' => 'Invalid Request [LCU-075]' ]
			], 400);
		}

		if (empty($License['name'])) {
			$License['name'] = $License['id'];
		}


		// Update License Map
		$sql = <<<SQL
		INSERT INTO license (id, company_id, code, name)
		VALUES (:l0, :c0, :lc, :ln)
		ON CONFLICT (id) DO
		UPDATE SET company_id = :c0, code = :lc, name = :ln
		SQL;
		$dbc_bong->query($sql, [
			':l0' => $License['id'],
			':c0' => $_SERVER['HTTP_OPENTHC_COMPANY'],
			':lc' => $License['code'],
			':ln' => $License['name'],
		]);

		if (empty($output_data)) {
			return $RES->withJSON([
				'data' => [],
				'meta' => [ 'detail' => 'Invalid Upload [LCU-076]' ]
			], 400);
		}

		if ( ! empty($output_data)) {

			file_put_contents($output_file, $output_data);

			if (preg_match('/(\w+ UPLOAD (01\w+)).+-canary-/', $output_data, $m)) {

				$req_code = $m[1];
				$req_ulid = $m[2];

				$rec = [];
				$rec['id'] = $req_ulid;
				$rec['license_id'] = $License['id'];
				$rec['name'] = $req_code;
				$rec['source_data'] = json_encode([
					'name' => $source_name,
					'data' => $output_data
				]);

				$dbc_bong->insert('log_upload', $rec);

			}

			// Symlink to License Code?
			$csv_pkid = 'ExternalIdentifier';
			switch ($source_type) {
				case 'area':
					$csv_type = 'section';
					break;
				case 'strain':
					$csv_pkid = 'Strain';
					$csv_type = 'variety';
					$License['id'] = '018NY6XC00L1CENSE000000000';
					break;
				case 'inventory':
					$csv_type = 'lot';
					break;
				case 'inventorytransfer':
					$csv_type = 'b2b_sale_item';
					break;
				case 'plant':
					$csv_type = 'crop';
					break;
				case 'product':
					// OK
					$csv_type = 'product';
					break;
				default:
					return $RES->withJSON([
						'data' => $source_type,
						'meta' => [ 'detail' => 'Invalid File Type [LCU-155]' ]
					], 400);
			}

			$csv_conn = fopen($output_file, 'r');
			$csv_head = [];

			while ($rec = fgetcsv($csv_conn)) {

				$skip = false;
				switch ($rec[0]) {
					case 'SubmittedBy':
					case 'SubmittedDate':
					case 'NumberRecords':
						$skip = true;
						break;
					case 'LicenseNumber';
					case 'Strain':
						$csv_head = $rec;
						$skip = true;
						break;
					case '226279':
						// My Canary Record
						$skip = true;
						break;
				}

				$txt = implode(',', $rec);
				if (preg_match('/\-canary\-/', $txt)) {
					// Jam this Record into the log_upload table?
					$skip = true;
				}

				if ($skip) {
					continue;
				}

				$row = array_combine($csv_head, $rec);
				$row['@id'] = $row[$csv_pkid];

				switch ($csv_type) {
					case 'b2b_sale_item':
					case 'inventorytransfer':
						// Special Case
						$this->upsert_b2b_sale($RES, $dbc_bong, $License, $csv_type, $row);
						break;
					default:
						$this->upsert_object($RES, $dbc_bong, $License, $csv_type, $row);
				}

				$rec_count++;

			}

		}

		return $RES->withJSON([
			'data' => [
				$output_file
			],
			'meta' => [
				'update' => $rec_count
			],
		], 200, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}

	/**
	 * UPSERT Standard Objects
	 */
	function upsert_object($RES, $dbc_bong, $License, $csv_type, $row)
	{
		if (!empty($row['LicenseNumber'])) {
			if ($row['LicenseNumber'] != $License['code']) {
				// This is a Problem
				// $License = $dbc_bong->fetchRow('SELECT * FROM license WHERE code = :l0', [
				// 	':l0' => $row['LicenseNumber']
				// ]);
				// return $RES->withJSON([
				// 	'data' => "{$row['LicenseNumber']} != {$License['code']}",
				// 	'meta' => [ 'detail' => '' ]
				// ], 409);
				throw new \Exception('License Conflict [LCU-190]');
			}
		}

		$rec_name = sprintf('%s %s', $csv_type, $row['@id']);
		switch ($csv_type) {
			case 'section':
				$rec_name = $row['Area'];
				break;
			case 'product':
				$rec_name = $row['Name'];
				break;
		}

		$sql = <<<SQL
		INSERT INTO {table} (id, license_id, name, data, hash)
		VALUES (:pk, :l0, :n0, :d0, :h0)
		ON CONFLICT (id) DO
		UPDATE SET updated_at = now(), stat = 100, hash = :h0, data = {table}.data || :d0
		WHERE {table}.id = :pk AND {table}.license_id = :l0 AND {table}.hash != :h0
		SQL;

		$sql = str_replace('{table}', $csv_type, $sql);

		$arg = [
			':pk' => $row['@id'],
			':l0' => $License['id'],
			':h0' => sha1(json_encode($row)),
			':n0' => $rec_name,
			':d0' => json_encode([
				'@result' => [],
				'@source' => $row,
			])
		];

		$dbc_bong->query($sql, $arg);
	}

	/**
	 * Special Case the B2B Sale Upsert
	 */
	function upsert_b2b_sale($RES, $dbc_bong, $License, $csv_type, $row)
	{
		// Insert the Sale Record?
		if ( ! empty($row['LicenseNumber'])) {
			if ($row['ToLicenseNumber'] != $License['code']) {
				// This is a Problem
				return $RES->withJSON([
					'data' => "{$row['ToLicenseNumber']} != {$License['code']}",
					'meta' => [ 'detail' => 'License Conflict [LCU-229]' ]
				], 409);
			}
		}

		$b2b_id = md5(sprintf('%s.%s.%s', $row['FromLicenseNumber'], $row['ToLicenseNumber'], $row['TransferDate']));

		// Find a B2B_Sale record FIRST
		$sql = <<<SQL
		INSERT INTO b2b_sale (id, name, license_id_source, license_id_target, flag, stat)
		VALUES (:b2b0, :n0, :ls0, :lt0, :f0, :s0)
		ON CONFLICT (id) DO NOTHING
		SQL;
		// UPDATE SET updated_at = now()
		// UPDATE SET updated_at :now(), data = :rd

		$arg = [
			':b2b0' => $b2b_id,
			':n0' => sprintf('Sold By: %s, Ship To: %s', $row['FromLicenseNumber'], $row['ToLicenseNumber']),
			':ls0' => $row['FromLicenseNumber'],
			':lt0' => $License['id'],
			':f0' => 0,
			':s0' => 100,
		];
		$dbc_bong->query($sql, $arg);

		// Then INSERT EACH ITEM

		$sql = <<<SQL
		INSERT INTO b2b_sale_item (id, b2b_sale_id, name, data)
		VALUES (:pk, :b2b0, :n0, :d0)
		ON CONFLICT (id) DO
		UPDATE SET updated_at = now(), data = :d0
		SQL;

		$arg = [
			':pk' => $row['@id'],
			':b2b0' => $b2b_id,
			':n0' => sprintf('Item %s', $row['@id']),
			':d0' => json_encode([
				'@source' => $row,
			])
		];

		$dbc_bong->query($sql, $arg);

	}

}
