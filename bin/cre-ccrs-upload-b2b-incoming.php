<?php
/**
 * Create Upload for B2B Incoming Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\CRE\CCRS;
use OpenTHC\Bong\CRE;

function _cre_ccrs_upload_b2b_incoming($cli_args)
{
	// Lock
	$key = implode('/', [ __FILE__, $cli_args['--license'] ]);
	$lock = new \OpenTHC\CLI\Lock($key);
	if ( ! $lock->create()) {
		syslog(LOG_DEBUG, sprintf('LOCK: "%s" Failed', $key));
		return 0;
	}

	// Check Cache
	$status = new \OpenTHC\Bong\CRE\CCRS\Status($cli_args['--license'], 'b2b/incoming');
	if (202 == $status->getStat()) {
		return 0;
	}

	// Get CRE Configuration
	$cfg = \OpenTHC\CRE::getConfig('usa-wa');
	$tz0 = new DateTimezone($cfg['tz']);
	$dt0 = new \DateTime('now', $tz0);
	$cre_service_key = $cfg['service-sk'];

	$dbc = _dbc();

	$License = _load_license($dbc, $cli_args['--license']);

	// Get Data
	$csv_data = [];

	// Go By Transaction
	$sql = <<<SQL
	SELECT b2b_incoming.id AS id
		, b2b_incoming.created_at
		, b2b_incoming.updated_at
		, b2b_incoming.data
		, b2b_incoming.stat
	FROM b2b_incoming
	WHERE b2b_incoming.target_license_id = :l0
	  AND b2b_incoming.stat IN (100, 102, 200, 404)
	--   AND b2b_incoming.created_at >= '2023-01-01' AND b2b_incoming.created_at < '2024-01-01'
	--   AND b2b_incoming.created_at >= '2024-01-01' AND b2b_incoming.created_at < '2025-01-01'
	  AND b2b_incoming.created_at >= '2025-01-01' AND b2b_incoming.created_at < '2026-01-01'
	ORDER BY b2b_incoming.id
	LIMIT 1000
	SQL;

	$arg = [ ':l0' => $License['id'] ];

	$res_b2b_incoming = $dbc->fetchAll($sql, $arg);
	foreach ($res_b2b_incoming as $b2b) {

		$res_b2b_incoming_stat = [];

		$dtC = new DateTime($b2b['created_at'], $tz0);
		$dtC->setTimezone($tz0);
		$dtU = new DateTime($b2b['updated_at'], $tz0);
		$dtU->setTimezone($tz0);

		$src_b2b = json_decode($b2b['data'], true);
		$src_b2b = $src_b2b['@source'];

		// Check Items
		$sql = <<<SQL
		SELECT b2b_incoming_item.*
		FROM b2b_incoming_item
		WHERE b2b_incoming_item.b2b_incoming_id = :b0
		AND b2b_incoming_item.stat IN (100, 102, 200, 400, 404)
		ORDER BY id
		SQL;

		$arg = [
			':b0' => $b2b['id'],
		];

		$res_b2b_incoming_item = $dbc->fetchAll($sql, $arg);
		foreach ($res_b2b_incoming_item as $b2b_incoming_item) {

			$cmd = '';
			switch ($b2b_incoming_item['stat']) {
			case 100:
			case 404:
				// INSERT & UPDATE stat to 102
				$cmd = 'INSERT';
				// $dbc->query('UPDATE b2b_incoming SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
				// 	':s0' => $b2b['id'],
				// ]);
				$dbc->query('UPDATE b2b_incoming_item SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $b2b_incoming_item['id'],
				]);
				// $res_b2b_incoming_stat = 102;
				break;
			case 102:
				// SECOND UPLOAD
				// $cmd = 'INSERT';
				break;
			case 200:
				// Move to 202 -- will get error from CCRS if NOT Good
				$cmd = 'UPDATE';
				$dbc->query('UPDATE b2b_incoming_item SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $b2b_incoming_item['id'],
				]);
				// $res_b2b_incoming_stat = 202;
				break;
			// What to do here?
			case 400:
				// Ignore
				// $dbc->query('UPDATE b2b_incoming_item SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
				// 	':s0' => $b2b_incoming_item['b2b_incoming_item_id'],
				// ]);
				break;
			default:
				throw new \Exception("Invalid B2B/Incoming/Item Status '{$b2b_incoming_item['stat']}'");
			}
			// switch ($x['b2b_incoming_item_stat']) {
			// 	case 400:
			// 		// Recycle
			// 		$dbc->query('UPDATE b2b_incoming_item SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
			// 			':s0' => $x['b2b_incoming_item_id'],
			// 		]);
			// 		break;
			// 	case 410:
			// 		// $cmd = 'DELETE'; // Move to 666 ?
			// 		// continue 2; // foreach
			// 		break;
			// 	default:
			// 		throw new \Exception("Invalid B2B/Incoming/Item Status '{$x['b2b_incoming_item_stat']}'");
			// }


			// Add to CSV
			if (empty($cmd)) {
				continue;
			}

			$src_b2b_item = json_decode($b2b_incoming_item['data'], true);
			$src_b2b_item = $src_b2b_item['@source'];

			$rec = [
				$src_b2b['source']['code'] // str_replace('-0', '', ) // FromLicenseNumber
				, $License['code'] // ToLicenseNumber
				, $src_b2b_item['source_inventory']['id'] ?: $src_b2b_item['source_lot']['id'] // FromInventoryExternalIdentifier
				, $src_b2b_item['target_inventory']['id'] ?: $src_b2b_item['target_lot']['id'] // ToInventoryExternalIdentifier
				, $src_b2b_item['unit_count'] // Quantity
				, $dtC->format('m/d/Y') // date('m/d/Y', strtotime($x['created_at']))
				, $b2b_incoming_item['id'] // , sprintf('%s/%s', $x['b2b_sale_id'], $x['b2b_sale_item_id'])
				, '-system-'
				, $dtC->format('m/d/Y')
				, '-system-'
				, $dtU->format('m/d/Y')
				, $cmd
			];

			// Skip Bad Data
			if (empty($rec[2])) {
				$dbc->query('UPDATE b2b_incoming_item SET stat = 400 WHERE id = :i0', [
					':i0' => $b2b_incoming_item['id'],
				]);
				continue;
			}
			if (empty($rec[3])) {
				$dbc->query('UPDATE b2b_incoming_item SET stat = 400 WHERE id = :i0', [
					':i0' => $b2b_incoming_item['id'],
				]);
				continue;
			}

			$csv_data[] = $rec;

		}

		// Update B2B Incoming Object Status Here
		$sql = <<<SQL
		SELECT count(id) AS c, stat
		FROM b2b_incoming_item
		WHERE b2b_incoming_item.b2b_incoming_id = :b0
		GROUP BY stat
		SQL;

		$arg = [
			':b0' => $b2b['id'],
		];
		$res_b2b_incoming_item_stat = $dbc->fetchAll($sql, $arg);
		switch (count($res_b2b_incoming_item_stat)) {
		case 0: // Problem
			break;
		case 1: // Awesome
			$stat = $res_b2b_incoming_item_stat[0]['stat'];
			echo "  UPDATE STAT {$b2b['stat']} => $stat\n";
			$dbc->query('UPDATE b2b_incoming SET stat = :s1 WHERE id = :b0', [
				':b0' => $b2b['id'],
				':s1' => $stat,
			]);
			break;
		}

	}

	// No Data, In Sync
	if (empty($csv_data)) {
		$status->setPush(202);
		return;
	}

	$req_ulid = _ulid();
	$csv_name = sprintf('InventoryTransfer_%s_%s.csv', $cre_service_key, $req_ulid);
	$csv_head = explode(',', 'FromLicenseNumber,ToLicenseNumber,FromInventoryExternalIdentifier,ToInventoryExternalIdentifier,Quantity,TransferDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);
	// $req_data = [ '-canary-', '-canary-', "B2B_INCOMING UPLOAD $req_ulid", '-canary-', 0, date('m/d/Y'), '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
	// array_unshift($csv_data, $req_data);

	$csv_temp = fopen('php://temp', 'w');

	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', $dt0->format('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', count($csv_data) ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}

	OpenTHC\Bong\CRE\CCRS\Upload::enqueue($License, $csv_name, $csv_temp);

	$status->setPush(102);

}
