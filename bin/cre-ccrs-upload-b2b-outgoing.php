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

	// Check Cache
	$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
		'license' => $cli_args['--license'],
		'object' => 'b2b/outgoing',
		'force' => $cli_args['--force']
	]);
	// if (202 == $uphelp->getStatus()) {
	// 	return 0;
	// }
	switch ($uphelp->getStatus()) {
	case 102: // Pending, So Wait?
		return;
	case 202: // Status Good
		break;
	}

	$dbc = _dbc();

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

	$License = _load_license($dbc, $cli_args['--license']);

	// CSV Data
	$req_ulid = _ulid();
	$csv_data = [];
	// $csv_data[] = [ '-canary-', '-canary-', "B2B_OUTGOING UPLOAD $req_ulid", '-canary-', '0', '', '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
	$csv_head = explode(',', 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,SalesTax,OtherTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$csv_name = sprintf('Sale_%s_%s.csv', $cre_service_key, $req_ulid);
	$col_size = count($csv_head);

	// Go By Transaction
	$sql = <<<SQL
	SELECT b2b_outgoing.id AS id
		, b2b_outgoing.created_at
		, b2b_outgoing.updated_at
		, b2b_outgoing.data
		, b2b_outgoing.stat
	FROM b2b_outgoing
	JOIN license AS source_license ON b2b_outgoing.source_license_id = source_license.id
	WHERE b2b_outgoing.source_license_id = :l0
	  AND  b2b_outgoing.stat IN (100, 102, 200, 404)
	ORDER BY b2b_outgoing.id
	LIMIT 1000
	SQL;

	$arg = [ ':l0' => $License['id'] ];

	$res_b2b_outgoing = $dbc->fetchAll($sql, $arg);
	foreach ($res_b2b_outgoing as $b2b) {

		$dtC = new DateTime($b2b['created_at'], $tz0);
		$dtU = new DateTime($b2b['updated_at'], $tz0);

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

			// echo "  ITEM: {$b2b_outgoing_item['id']} = {$b2b_outgoing_item['stat']}\n";

			$src_b2b_item = json_decode($b2b_outgoing_item['data'], true);
			$src_b2b_item = $src_b2b_item['@source'];


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
				// SECOND UPLOAD
				$cmd = 'INSERT';
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
				, '0' // OtherTax
				, $src_b2b['id'] // SaleExternalIdentifier
				, $src_b2b_item['id'] // SaleDetailExternalIdentifier
				, '-system-' // CreatedBy
				, $dtC->format('m/d/Y') // CreatedDate
				, '-system-' // UpdatedBy
				, $dtU->format('m/d/Y') // UpdatedDate
				, $cmd // OPERATION
			];

			if (empty($rec[1])) {
				// var_dump($src_b2b);
				// var_dump($src_b2b_item);
				echo "./bin/sync.php --company {$License['company_id']} --license {$License['id']} --object b2b-outgoing --object-id {$src_b2b['id']}\n";
				// continue;
				throw new \Exception('Invalid B2B Missing Target [UBO-130]');
			}
			if (empty($rec[2])) {
				// var_dump($src_b2b);
				var_dump($b2b_outgoing_item);
				echo "./bin/sync.php --company {$License['company_id']} --license {$License['id']} --object b2b-outgoing --object-id {$src_b2b['id']}\n";
				continue 2;
				// throw new \Exception('Invalid B2B Missing Inventory [UBO-135]');
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
			$dbc->query('UPDATE b2b_outgoing SET stat = :s1 WHERE id = :b0', [
				':b0' => $b2b['id'],
				':s1' => $stat,
			]);
			echo "  UPDATE STAT {$b2b['stat']} => $stat\n";
			break;
		}

	}

	$row_size = count($csv_data);
	if ($row_size <= 1) {
		$uphelp->setStatus(202);
		return;
	}

	$csv_temp = fopen('php://temp', 'w');
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

	$uphelp->setStatus(102);

}
