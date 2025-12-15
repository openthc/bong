<?php
/**
 * Create Upload for B2B Incoming Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_b2b_outgoing($cli_args)
{
	// Lock
	$key = implode('/', [ __FILE__, $cli_args['--license'] ]);
	$lock = new \OpenTHC\CLI\Lock($key);
	if ( ! $lock->create()) {
		syslog(LOG_DEBUG, sprintf('LOCK: "%s" Failed', $key));
		return 0;
	}

	// Get CRE Configuration
	$cfg = \OpenTHC\CRE::getConfig('usa-wa');
	$tz0 = new DateTimezone($cfg['tz']);
	$dt0 = new \DateTime('now', $tz0);
	$cre_service_key = $cfg['service-sk'];

	$dbc = _dbc();
	$License = _load_license($dbc, $cli_args['--license']);

	// Check Cache
	$status = new \OpenTHC\Bong\CRE\CCRS\Status($License['id'], 'b2b/outgoing');
	$chk = $status->getStat();
	// echo "STAT: $chk\n";
	if (202 == $chk) {
		return 0;
	}


//                              View "public.b2b_outgoing_full"
//             Column            |           Type           | Collation | Nullable | Default
// ------------------------------+--------------------------+-----------+----------+---------
//  id                           | character varying(64)    |           |          |
//  source_license_id            | character varying(64)    |           |          |
//  target_license_id            | character varying(64)    |           |          |
//  created_at                   | timestamp with time zone |           |          |
//  updated_at                   | timestamp with time zone |           |          |
//  stat                         | integer                  |           |          |
//  flag                         | integer                  |           |          |
//  hash                         | character varying(64)    |           |          |
//  name                         | text                     |           |          |
//  data                         | jsonb                    |           |          |
//  b2b_outgoing_item_id         | character varying(64)    |           |          |
//  b2b_outgoing_item_flag       | integer                  |           |          |
//  b2b_outgoing_item_stat       | integer                  |           |          |
//  b2b_outgoing_item_created_at | timestamp with time zone |           |          |
//  b2b_outgoing_item_updated_at | timestamp with time zone |           |          |
//  b2b_outgoing_item_hash       | character varying(64)    |           |          |
//  b2b_outgoing_item_name       | text                     |           |          |
//  b2b_outgoing_item_data       | jsonb                    |           |          |



	// CSV Data
	$csv_data = [];

	// Go By Transaction
	$sql = <<<SQL
	SELECT b2b_outgoing.id AS id
		, b2b_outgoing.created_at
		, b2b_outgoing.updated_at
		, b2b_outgoing.data
		, b2b_outgoing.stat
	FROM b2b_outgoing
	WHERE b2b_outgoing.source_license_id = :l0
	  AND b2b_outgoing.stat IN (100, 102, 200, 404)
	  -- AND b2b_outgoing.created_at >= '2023-01-01' AND b2b_outgoing.created_at < '2024-01-01'
	  -- AND b2b_outgoing.created_at >= '2024-01-01' AND b2b_outgoing.created_at < '2025-01-01'
	  AND b2b_outgoing.created_at >= '2025-01-01' AND b2b_outgoing.created_at < '2026-01-01'
	ORDER BY b2b_outgoing.id
	LIMIT 1000
	SQL;

	$arg = [];
	$arg[':l0'] = $License['id'];

	if ( ! empty($cli_args['--object-id'])) {

		$arg[':oid1'] = $cli_args['--object-id'];

		$sql = <<<SQL
		SELECT b2b_outgoing.id AS id
			, b2b_outgoing.created_at
			, b2b_outgoing.updated_at
			, b2b_outgoing.data
			, b2b_outgoing.stat
		FROM b2b_outgoing
		WHERE b2b_outgoing.source_license_id = :l0
		  AND b2b_outgoing.id = :oid1
		-- AND b2b_outgoing.stat IN (100, 102, 200, 400, 404)
		-- AND b2b_outgoing.created_at >= '2023-01-01' AND b2b_outgoing.created_at < '2024-01-01'
		-- AND b2b_outgoing.created_at >= '2024-01-01' AND b2b_outgoing.created_at < '2025-01-01'
		-- AND b2b_outgoing.created_at >= '2025-01-01' AND b2b_outgoing.created_at < '2026-01-01'
		ORDER BY b2b_outgoing.id
		LIMIT 1000
		SQL;

	}

	$res_b2b_outgoing = $dbc->fetchAll($sql, $arg);
	foreach ($res_b2b_outgoing as $b2b) {

		$dtC = new DateTime($b2b['created_at'], $tz0);
		$dtC->setTimezone($tz0);
		$dtU = new DateTime($b2b['updated_at'], $tz0);
		$dtU->setTimezone($tz0);

		$src_b2b = json_decode($b2b['data'], true);
		$src_b2b = $src_b2b['@source'];

		// Check Items
		$sql = <<<SQL
		SELECT b2b_outgoing_item.*
		FROM b2b_outgoing_item
		WHERE b2b_outgoing_item.b2b_outgoing_id = :b0
		  AND b2b_outgoing_item.stat IN (100, 102, 200, 400, 404)
		ORDER BY id
		SQL;

		$arg = [
			':b0' => $b2b['id'],
		];

		$res_b2b_outgoing_item = $dbc->fetchAll($sql, $arg);
		foreach ($res_b2b_outgoing_item as $b2b_outgoing_item) {

			$cmd = '';
			switch ($b2b_outgoing_item['stat']) {
			case 100:
			case 404:
				// INSERT & UPDATE stat to 102
				$cmd = 'INSERT';
				// $dbc->query('UPDATE b2b_outgoing SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
				// 	':s0' => $b2b['id'],
				// ]);
				$dbc->query('UPDATE b2b_outgoing_item SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $b2b_outgoing_item['id'],
				]);
				break;
			case 102:
				$cmd = 'UPDATE';
				$dbc->query('UPDATE b2b_outgoing_item SET stat = 200, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $b2b_outgoing_item['id'],
				]);
				break;
			case 200:
				// Move to 202 -- will get error from CCRS if NOT Good
				$cmd = 'UPDATE';
				$dbc->query('UPDATE b2b_outgoing_item SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $b2b_outgoing_item['id'],
				]);
				break;
			// What to do here?
			case 400:
				// Ignore
				// $dbc->query('UPDATE b2b_outgoing_item SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
				// 	':s0' => $b2b_outgoing_item['b2b_outgoing_item_id'],
				// ]);
				break;
			default:
				throw new \Exception("Invalid Item Status '{$b2b_outgoing_item['stat']}'");
			}

			// Add to CSV
			if (empty($cmd)) {
				continue;
			}

			$src_b2b_item = json_decode($b2b_outgoing_item['data'], true);
			$src_b2b_item = $src_b2b_item['@source'];

			$rec = [
				$License['code'] // LicenseNumber
				, $src_b2b['target']['code'] ?: $src_b2b['target_license']['code'] // SoldToLicenseNumber
				, $src_b2b_item['inventory']['id'] ?: $src_b2b_item['lot']['id'] // InventoryExternalIdentifier
				, '' // PlantExternalIdentifier
				, 'Wholesale' // SaleType
				, $dtC->format('m/d/Y') // SaleDate
				, floatval($src_b2b_item['unit_count']) // Quantity
				, floatval($src_b2b_item['unit_price']) // UnitPrice
				, '0' // Discount
				, '0' // SalesTax
				, '0' // CannabisExciseTax
				, $src_b2b['id'] // SaleExternalIdentifier
				, $src_b2b_item['id'] // SaleDetailExternalIdentifier
				, '-system-' // CreatedBy
				, $dtC->format('m/d/Y') // CreatedDate
				, '-system-' // UpdatedBy
				, $dtU->format('m/d/Y') // UpdatedDate
				, $cmd // OPERATION
			];

			if ($rec[0] == $rec[1]) {
				$dbc->query('UPDATE b2b_outgoing SET stat = 409 WHERE id = :b0 AND source_license_id = :l0', [
					':l0' => $License['id'],
					':b0' => $b2b['id'],
				]);
				continue;
			}


			if (empty($rec[6])) {
				// var_dump($src_b2b);
				// var_dump($src_b2b_item);
				echo "  DELETE FROM b2b_outgoing_item WHERE b2b_outgoing_id = '{$src_b2b['id']}' AND id = '{$src_b2b_item['id']}';\n";
				echo "  ./bin/sync.php --company {$License['company_id']} --license {$License['id']} --object b2b-outgoing --object-id {$src_b2b['id']}\n";
				// continue;
				// throw new \Exception('Invalid B2b Missing Quantity [UBO-140]');
			}
			// Price
			// if (0 == strlen($rec[7])) {
			// 	// var_dump($src_b2b);
			// 	// var_dump($src_b2b_item);
			// 	echo "./bin/sync.php --company {$License['company_id']} --license {$License['id']} --object b2b-outgoing --object-id {$src_b2b['id']}\n";
			// 	// continue;
			// 	throw new \Exception('Invalid B2b Missing Price [UBO-145]');
			// }

			// var_dump($b2b_outgoing_item);
			// unset($src_b2b['item_list']);
			// var_dump($src_b2b);
			// var_dump($src_b2b_item);
			// var_dump($src);
			// var_dump($rec); exit;

			$csv_data[] = $rec;

		}

		// Update B2B Outgoing Status w/Item Status
		$sql = <<<SQL
		SELECT count(id) AS c, stat
		FROM b2b_outgoing_item
		WHERE b2b_outgoing_item.b2b_outgoing_id = :b0
		GROUP BY stat
		SQL;

		$arg = [
			':b0' => $b2b['id'],
		];
		$res_b2b_outgoing_item_stat = $dbc->fetchAll($sql, $arg);
		switch (count($res_b2b_outgoing_item_stat)) {
		case 0: // Problem
			break;
		case 1: // Awesome
			$stat = $res_b2b_outgoing_item_stat[0]['stat'];
			// echo "  UPDATE STAT {$b2b['stat']} => $stat\n";
			$dbc->query('UPDATE b2b_outgoing SET stat = :s1 WHERE id = :b0', [
				':b0' => $b2b['id'],
				':s1' => $stat,
			]);
			break;
		}

		// Check for File
		$arg = [];
		$arg[':b2b0'] = $b2b['id'];

		$sql = <<<SQL
		SELECT id
		FROM b2b_outgoing_file
		WHERE id = :b2b0
		SQL;

		$chk = $dbc->fetchRow($sql, $arg);
		if (empty($chk['id'])) {
			// echo "  Create Manifest File for {$b2b['id']}\n";
			echo "  ./bin/cre-ccrs.php upload-b2b-outgoing-file --license {$License['id']} --object-id {$b2b['id']}\n";
		}

	}

	// No Data, In Sync
	if (empty($csv_data)) {
		$status->setPush(202);
		return;
	}

	$req_ulid = _ulid();
	$csv_name = sprintf('Sale_%s_%s.csv', $cre_service_key, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,RetailSalesTax,CannabisExciseTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);
	// $req_data = [ '-canary-', '-canary-', "B2B_OUTGOING UPLOAD $req_ulid", '-canary-', '0', '', '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
	// array_unshift($csv_data, $req_data);

	$csv_temp = fopen('php://temp', 'w');

	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', $dt0->format('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', count($csv_data) ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}

	// OpenTHC\Bong\CRE\CCRS\Upload::enqueue($License, $csv_name, $csv_temp);

	fseek($csv_temp, 0);
	$csv_data = stream_get_contents($csv_temp);

	$rec = [];
	$rec['id'] = $req_ulid;
	$rec['license_id'] = $License['id'];
	$rec['name'] = sprintf('B2B_OUTGOING UPLOAD %s', $req_ulid);
	$rec['source_data'] = json_encode([
		'name' => $csv_name,
		'data' => $csv_data
	]);

	$output_file = $dbc->insert('log_upload', $rec);


	$status->setPush(102);

	return $req_ulid;
}
