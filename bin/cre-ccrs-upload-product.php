<?php
/**
 * Create Upload for Product Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_product($cli_args)
{
	$lic = $cli_args['--license'];

	// Check Cache
	$rdb = \OpenTHC\Service\Redis::factory();
	$chk = $rdb->hget(sprintf('/license/%s', $lic), 'product/stat');
	switch ($chk) {
		case 102:
		case 200:
			return(0);
			break;
		default:
			syslog(LOG_DEBUG, "license:{$lic}; product-stat={$chk}");
	}

	$dbc = _dbc();
	$License = _load_license($dbc, $lic);

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));

	// Get Data
	$csv_data = [];

	$sql = <<<SQL
	SELECT *
	FROM product
	WHERE license_id = :l0
	SQL;

	$res_product = $dbc->fetchAll($sql, [ ':l0' => $License['id'] ]);
	foreach ($res_product as $product) {

		$product['data'] = json_decode($product['data'], true);
		$product_source = $product['data']['@source'];

		if ('018NY6XC00PR0DUCTTYPE00000' == $product_source['type']) {
			$dbc->query('UPDATE product SET stat = 540 WHERE id = :p0', [
				':p0' => $product['id']
			]);
			continue;
		}
		if ('018NY6XC00PR0DUCTTYPE00001' == $product_source['type']) {
			$dbc->query('UPDATE product SET stat = 546 WHERE id = :p0', [
				':p0' => $product['id']
			]);
			continue;
		}

		$cmd = '';
		switch ($product['stat']) {
			case 100:
				$cmd = 'INSERT';
				$dbc->query('UPDATE product SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $product['id'],
				]);
				break;
			case 102:
				$cmd = 'INSERT';
				break;
			case 200:
				$cmd = 'UPDATE';
				$dbc->query('UPDATE product SET stat = 202 WHERE id = :s0', [
					':s0' => $product['id'],
				]);
				break;
			case 202:
				// Ignore
				break;
			case 404:
				// $cmd = 'INSERT';
				// $dbc->query('UPDATE product SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
				// 	':s0' => $product['id'],
				// ]);
				break;
			case 403:
				// Ignore
				break;
			case 410:
				// $cmd = 'DELETE';
				// continue 2; // foreach
				break;
			case 540:
			case 546:
				// Ignore
				break;
			default:
				throw new \Exception("Invalid Product Status '{$product['stat']}'");
		}
		if (empty($cmd)) {
			continue;
		}


		$dtC = new DateTime($product['created_at']);
		$dtU = new DateTime($product['updated_at']);

		$row = [];

		try {
			$row = [
				$License['code']
				, CCRS::map_product_type0($product_source['type'])
				, CCRS::map_product_type1($product_source['type'])
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
			var_dump($License);
			var_dump($product);
			echo "BAD PRODUCT\n";
			exit(0);
		}

		if (empty($row[1])) {
			var_dump($row);
			var_dump($product);
			echo "FAIL 1 \n";
			exit;
		}
		if (empty($row[2])) {
			var_dump($product);
			echo "FAIL 2 \n";
			exit;
		}
		if (empty($row[3])) {
			var_dump($product);
			echo "FAIL 3 \n";
			exit;
		}

		switch ($product_source['package']['type']) {
			// case 'bulk':
			// 	$row[5] = 0;
			// 	break;
			case 'each':
				$row[5] = sprintf('%0.2f', $product_source['package']['unit']['weight']);
				break;
		}

		$csv_data[] = $row;

	}

	// No Data, In Sync
	if (empty($csv_data)) {
		$rdb->hset(sprintf('/license/%s', $License['id']), 'product/stat', 200);
		$rdb->hset(sprintf('/license/%s', $License['id']), 'product/stat/time', time());
		$rdb->hset(sprintf('/license/%s', $License['id']), 'product/sync', 200);
		return;
	}

	$req_ulid = _ulid();

	$csv_data[] = [ '-canary-', '-canary-', '-canary-', "PRODUCT UPLOAD $req_ulid", '', '0', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];

	$api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$csv_name = sprintf('Product_%s_%s.csv', $api_code, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,UnitWeightGrams,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);

	$csv_temp = fopen('php://temp', 'w');
	$row_size = count($csv_data);

	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}
	fseek($csv_temp, 0);

	// Upload
	_upload_to_queue_only($License, $csv_name, $csv_temp);

	$rdb->hset(sprintf('/license/%s', $License['id']), 'product/stat', 102);
	$rdb->hset(sprintf('/license/%s', $License['id']), 'product/stat/time', time());
	$rdb->hset(sprintf('/license/%s', $License['id']), 'product/sync', 100);

}
