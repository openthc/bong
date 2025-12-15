<?php
/**
 * Create Upload for Product Data
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Product;

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

class Export
{
	/**
	 *
	 */
	function __construct($License)
	{
		$this->_License = $License;
		$this->_cre_config = \OpenTHC\CRE::getConfig('usa-wa');
		$this->_tz0 = new \DateTimezone($this->_cre_config['tz']);
		$this->_dt0 = new \DateTime('now', $this->_tz0);
	}

 	/**
	 *
	 */
	function create($force=false)
	{
		// Check Cache
		$status = new \OpenTHC\Bong\CRE\CCRS\Status($this->_License['id'], 'product');
		$chk = $status->getStat();
		switch ($chk) {
			case 202:
				return;
		}

		$dbc = _dbc();
		// $dbc->query('BEGIN');

		// Get Data
		$csv_data = [];

		$sql = <<<SQL
		SELECT *
		FROM product
		WHERE license_id = :l0
		  AND stat IN (100, 102, 200, 404)
		ORDER BY id
		SQL;

		$res_product = $dbc->fetchAll($sql, [ ':l0' => $this->_License['id'] ]);
		foreach ($res_product as $product) {

			$product['data'] = json_decode($product['data'], true);
			$product_source = $product['data']['@source'];

			if ('018NY6XC00PR0DUCTTYPE00000' == $product_source['type']) {
				$dbc->query('UPDATE product SET stat = 422 WHERE id = :p0', [
					':p0' => $product['id']
				]);
				continue;
			}
			if ('018NY6XC00PR0DUCTTYPE00001' == $product_source['type']) {
				$dbc->query('UPDATE product SET stat = 422 WHERE id = :p0', [
					':p0' => $product['id']
				]);
				continue;
			}

			$cmd = '';
			switch ($product['stat']) {
				case 100:
				case 404:
					$cmd = 'INSERT';
					$dbc->query('UPDATE product SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $product['id'],
					]);
					break;
				case 102:
					$cmd = 'UPDATE';
					$dbc->query('UPDATE product SET stat = 200, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $product['id'],
					]);
					break;
				case 200:
					$cmd = 'UPDATE';
					$dbc->query('UPDATE product SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $product['id'],
					]);
					break;
				case 400:
					// $cmd = 'INSERT';
					// $dbc->query('UPDATE product SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					// 	':s0' => $product['id'],
					// ]);
					break;
				case 410:
					// $cmd = 'DELETE'; ?
					// continue 2; // foreach
					break;
				default:
					throw new \Exception("Invalid Product Status '{$product['stat']}'");
			}
			if (empty($cmd)) {
				continue;
			}


			$dtC = new \DateTime($product['created_at'], $this->_tz0);
			$dtC->setTimezone($this->_tz0);
			$dtU = new \DateTime($product['updated_at'], $this->_tz0);
			$dtU->setTimezone($this->_tz0);

			$PT0 = new \OpenTHC\CRE\CCRS\Product\Type($product_source['type']);

			$row = [];

			try {
				$row = [
					$this->_License['code']
					, $PT0->getCategoryName()
					, $PT0->getTypeName()
					, CCRS::sanatize($product['name'], 75)
					, CCRS::sanatize($product_source['note'])
					, 0 // 5; sprintf('%0.2f', $product_source['package']['unit']['weight']) // sprintf('%0.2f', ('each' == $product['package_type'] ? $product['package_pack_qom'] : 0)) // if BULK use ZERO? // UnitWeightGrams
					, $product['id']
					, '-system-'
					, $dtC->format('m/d/Y')
					, '-system-'
					, $dtU->format('m/d/Y')
					, $cmd
				];
			} catch (\Exception $e) {

				// var_dump($License);
				// var_dump($product);
				echo "BAD PRODUCT\n";
				echo $e->getMessage();
				echo "\n";

				// How to write the JSON result?
				$sql = <<<SQL
				UPDATE product
				SET stat = 400
					, data = jsonb_set(data, '{ "@result" }', '{ "code": 400, "data": [ "Invalid OpenTHC Product Type" ] }')
				WHERE id = :s0 AND license_id = :l0
				SQL;

				$arg = [
					':l0' => $this->_License['id'],
					':s0' => $product['id']
				];

				// echo $dbc->_sql_debug($sql, $arg);

				$dbc->query($sql, $arg);

				continue;
			}

			if (empty($row[1])) {
				var_dump($row);
				var_dump($product);
				echo "FAIL 1 \n";
				continue;
			}
			if (empty($row[2])) {
				var_dump($product);
				echo "FAIL 2 \n";
				continue;
			}
			if (empty($row[3])) {
				var_dump($product);
				echo "FAIL 3 \n";
				continue;
			}

			switch ($product_source['package']['type']) {
				// case 'bulk':
				// 	$row[5] = 0;
				// 	break;
				case 'each':
					$row[5] = sprintf('%0.2f', $product_source['package']['unit']['weight']);
					break;
				case 'pack':
					$w = $product_source['package']['unit']['weight'] * $product_source['package']['unit']['count'];
					$row[5] = sprintf('%0.2f', $w);
					break;
			}

			$csv_data[] = $row;

		}

		// No Data, In Sync
		$row_size = count($csv_data);
		if (0 == $row_size) {
			$status->setPush(202);
			return;
		}

		$req_ulid = _ulid();

		$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
		$csv_name = sprintf('Product_%s_%s.csv', $api_code, $req_ulid);
		$csv_head = explode(',', 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,UnitWeightGrams,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
		$col_size = count($csv_head);

		$csv_temp = fopen('php://temp', 'w');
		$row_size = count($csv_data);

		CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
		CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
		CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
		CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
		foreach ($csv_data as $row) {
			CCRS::fputcsv_stupidly($csv_temp, $row);
		}

		$res = \OpenTHC\Bong\CRE\CCRS\Upload::enqueue($this->_License, $csv_name, $csv_temp);
		var_dump($res);

		// fseek($csv_temp, 0);
		// $csv_data = stream_get_contents($csv_temp);

		// $rec = [];
		// $rec['id'] = $req_ulid;
		// $rec['license_id'] = $this->_License['id'];
		// $rec['name'] = sprintf('PRODUCT UPLOAD %s', $req_ulid);
		// $rec['source_data'] = json_encode([
		// 	'name' => $csv_name,
		// 	'data' => $csv_data
		// ]);

		// $output_file = $dbc->insert('log_upload', $rec);
		// $dbc->query('COMMIT');

		$status->setPush(102);

		return $req_ulid;
	}

}
