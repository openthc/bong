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
	// Check Cache
	$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
		'license' => $cli_args['--license'],
		'object' => 'b2b/incoming',
		'force' => $cli_args['--force']
	]);
	if (202 == $uphelp->getStatus()) {
		return 0;
	}

	$dbc = _dbc();

	$cfg = \OpenTHC\CRE::getConfig('usa/wa');
	$tz0 = new DateTimezone($cfg['tz']);
	$cre_service_key = $cfg['service-sk'];

	$License = _load_license($dbc, $cli_args['--license']);

	// Get Data
	$csv_data = [];

	$sql = <<<SQL
	SELECT b2b_incoming.*,
	b2b_incoming_item.id AS b2b_incoming_item_id,
	b2b_incoming_item.name AS b2b_incoming_item_name,
	b2b_incoming_item.data AS b2b_incoming_item_data,
	b2b_incoming_item.stat AS b2b_incoming_item_stat
	FROM b2b_incoming
	JOIN b2b_incoming_item ON b2b_incoming.id = b2b_incoming_item.b2b_incoming_id
	WHERE b2b_incoming.target_license_id = :l0
	  AND b2b_incoming_item.stat IN (100, 102, 200)
	SQL;
	$res_b2b_incoming_item = $dbc->fetchAll($sql, [ ':l0' => $License['id'] ]);

	foreach ($res_b2b_incoming_item as $x) {

		$x['data'] = json_decode($x['data'], true);
		$x['b2b_incoming_item_data'] = json_decode($x['b2b_incoming_item_data'], true);

		$dtC = new \DateTime($x['created_at'], $tz0);
		$dtC->setTimezone($tz0);

		$dtU = new \DateTime($x['updated_at'], $tz0);
		$dtU->setTimezone($tz0);

		$cmd = '';
		switch ($x['b2b_incoming_item_stat']) {
			case 100:
				$cmd = 'INSERT'; // Moves to 404 via CCRS Response
				$dbc->query('UPDATE b2b_incoming_item SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $x['b2b_incoming_item_id'],
				]);
				break;
			case 102:
				$cmd = 'INSERT';
				break;
			case 200:
				// Move to 202 -- will get error from CCRS if NOT Good
				$cmd = 'UPDATE';
				$dbc->query('UPDATE b2b_incoming_item SET stat = 202 WHERE id = :s0', [
					':s0' => $x['b2b_incoming_item_id'],
				]);
				break;
			case 202:
				// Ignore
				break;
			case 400:
				// Recycle
				$dbc->query('UPDATE b2b_incoming_item SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $x['b2b_incoming_item_id'],
				]);
				break;
			case 404:
				// Try Insert and Recycle
				$cmd = 'INSERT';
				$dbc->query('UPDATE b2b_incoming_item SET stat = 100, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $x['b2b_incoming_item_id'],
				]);
				break;
			case 410:
				// $cmd = 'DELETE'; // Move to 666 ?
				// continue 2; // foreach
				break;
			default:
				throw new \Exception("Invalid B2B/Incoming/Item Status '{$x['b2b_incoming_item_stat']}'");
		}

		if (empty($cmd)) {
			continue;
		}


		$rec = [
			$x['data']['@source']['source']['code'], // FromLicenseNumber
			$x['data']['@source']['target']['code'] ?: $License['code']  // ToLicenseNumber
			, $x['b2b_incoming_item_data']['@source']['source_lot']['id'] //   ['origin_lot_id'] // FromInventoryExternalIdentifier
			, $x['b2b_incoming_item_data']['@source']['target_lot']['id'] //   ['target_lot_id'] // ToInventoryExternalIdentifier
			, $x['b2b_incoming_item_data']['@source']['unit_count'] // Quantity
			, $dtC->format('m/d/Y') // date('m/d/Y', strtotime($x['created_at']))
			, $x['b2b_incoming_item_id'] // , sprintf('%s/%s', $x['b2b_sale_id'], $x['b2b_sale_item_id'])
			, '-system-'
			, $dtC->format('m/d/Y')
			, '-system-'
			, $dtU->format('m/d/Y')
			, $cmd
		];

		$csv_data[] = $rec;
	}

	// No Data, In Sync
	if (empty($csv_data)) {
		$uphelp->setStatus(202);
		return;
	}

	$req_ulid = _ulid();
	// $req_data = [ '-canary-', '-canary-', "B2B_INCOMING UPLOAD $req_ulid", '-canary-', 0, date('m/d/Y'), '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
	// array_unshift($csv_data, $req_data);

	$csv_head = explode(',', 'FromLicenseNumber,ToLicenseNumber,FromInventoryExternalIdentifier,ToInventoryExternalIdentifier,Quantity,TransferDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$csv_name = sprintf('InventoryTransfer_%s_%s.csv', $cre_service_key, $req_ulid);
	$col_size = count($csv_head);
	$csv_temp = fopen('php://temp', 'w');

	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', count($csv_data) ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}

	// Upload
	fseek($csv_temp, 0);

	// fpassthru($csv_temp);
	// return;
	_upload_to_queue_only($License, $csv_name, $csv_temp);

	$uphelp->setStatus(102);

}
