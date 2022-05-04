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
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{

		$type = $_SERVER['CONTENT_TYPE']; // text/csv
		$size = $_SERVER['CONTENT_LENGTH'];

		$csv_name = $_SERVER['HTTP_CONTENT_NAME'];

		$output_data = file_get_contents('php://input');

		if ( ! empty($output_data)) {

			$output_file = sprintf('%s/var/ccrs-outgoing/%s', APP_ROOT, $csv_name);
			file_put_contents($output_file, $output_data);

			$hash = $_SERVER['HTTP_OPENTHC_SERVICE'] . $_SERVER['HTTP_OPENTHC_COMPANY'] . $_SERVER['HTTP_OPENTHC_LICENSE'];
			$hash = md5($hash);

			$sql_file = sprintf('%s/var/%s.sqlite', APP_ROOT, $hash);
			$sql_init = is_file($sql_file);
			$dbc = new \Edoceo\Radix\DB\SQL(sprintf('sqlite:%s', $sql_file));

			if ( ! $sql_init) {

				$dbc->query('CREATE TABLE base_option (key PRIMARY KEY, val)');

				$dbc->query('CREATE TABLE company (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE license (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE product (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE variety (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE section (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				// $dbc->query('CREATE TABLE vehicle (id PRIMARY KEY, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE crop (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE lot (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE lab_result (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');


				$dbc->query('CREATE TABLE b2b_incoming (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE b2b_incoming_item (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, b2b_incoming_id, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE b2b_outgoing (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE b2b_outgoing_item (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, b2b_outgoing_id, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE b2c_outgoing (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE b2c_outgoing_item (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, b2c_outgoing_id, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE ccrs_outgoing (id PRIMARY KEY NOT NULL, cre_stat, cre_meta, type, data)');

			}

			$csv_pkid = 'ExternalIdentifier';
			$csv_type = preg_match('/^([a-z]+)_/', $csv_name, $m) ? $m[1] : null;
			switch ($csv_type) {
				case 'strain':
					$csv_pkid = 'Strain';
					$csv_type = 'variety';
					break;
				case 'inventory':
					$csv_type = 'lot';
					break;
				case 'product':
					// OK
					break;
				default:
					return $RES->withJSON([
						'data' => null,
						'meta' => [ 'detail' => 'Invalid File Type' ]
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
					$skip = true;
				}

				if ($skip) {
					continue;
				}

				$row = array_combine($csv_head, $rec);

				$sql = <<<SQL
				INSERT INTO %s (id, created_at, updated_at, data)
				VALUES (:pk, :ct, :ut, :rd)
				ON CONFLICT (id) DO
				UPDATE SET updated_at = :ut, data = :rd
				WHERE id = :pk
				SQL;

				$sql = sprintf($sql, $csv_type);

				$arg = [
					':pk' => $row[$csv_pkid],
					':ct' => date(\DateTime::RFC3339),
					':ut' => date(\DateTime::RFC3339),
					':rd' => json_encode($row)
				];

				$dbc->query($sql, $arg);

			}

		}

		return $RES->withJSON([
			'data' => [
				$output_file
			],
			'meta' => [],
		]);

	}

}
