<?php
/**
 * Use Curl to upload to the CCRS site
 *
 * SPDX-License-Identifier: MIT
 *
 * Get the cookies from var/
 * Upload the files one at a time, was some transactional lock like issues with bulk
 */

namespace OpenTHC\Bong\CRE\CCRS\Inventory\Adjust;

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
		$this->_cre_config = \OpenTHC\CRE::getConfig('usa/wa');
		$this->_tz0 = new \DateTimezone($this->_cre_config['tz']);
		$this->_dt0 = new \DateTime('now', $this->_tz0);
	}

}


function _cre_ccrs_upload_inventory_adjust($cli_args)
{
	// Check Cache
	$status = new \OpenTHC\Bong\CRE\CCRS\Status($this->_License['id'], 'inventory/adjust');
	$chk = $status->getStat();
	switch ($chk) {
		case 202:
			return;
	}

	$dbc = _dbc();

	$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

	if ('01CAV11RYRHF33Z2324JNF0EJS' != $cli_args['--license']) {
		return 0;
	}

	$License = _load_license($dbc, $cli_args['--license']);

	$req_ulid = _ulid();

	$csv_data = [];
	$csv_data[] = [ '-canary', '-canary-','-canary-',"INVENTORY_ADJUST UPLOAD $req_ulid",'-canary-','-canary-','-canary-','-canary-','-canary-','-canary-','-canary-','-canary-' ];

	// Get Data
	$sql = <<<SQL
	SELECT *
	FROM inventory_adjust
	WHERE license_id = :l0
	  AND stat IN (100, 102, 200)
	ORDER BY stat ASC, updated_at ASC
	LIMIT 2500
	SQL;

	$res_inv_adj = $dbc->fetchAll($sql, [ ':l0' => $License['id'] ]);
	foreach ($res_inv_adj as $adj) {

		$cmd = '';
		switch ($adj['stat']) {
			case 100:
			case 404:
				$cmd = 'INSERT';
				$dbc->query('UPDATE inventory_adjust SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					':s0' => $adj['id'],
				]);
				break;
			case 102:
				// $cmd = 'INSERT';
				break;
			case 200:
				$cmd = 'UPDATE';
				$dbc->query('UPDATE inventory_adjust SET stat = 202 WHERE id = :s0', [
					':s0' => $inv['id'],
				]);
				break;
			case 202:
				// Fully Uploaded
				break;
			case 400:
				$cmd = 'UPDATE';
				break;
			// case 404:
			// 	$cmd = 'INSERT';
			// 	break;
			case 410:
			case 666:
				// $cmd = 'DELETE';
				break;
			default:
				throw new \Exception("Invalid Inventory_Adjust Stat '{$adj['stat']}'");
		}

		if (empty($cmd)) {
			// echo "SKIP: {$inv['id']}\n";
			continue;
		}

		switch (strtoupper($adj_data->type)) {
			case 'DESTRUCTION':
			case 'LOST':
			case 'OTHER':
			case 'RECONCILIATION':
			case 'RETURNEDLABSAMPLE':
			case 'SEIZURE':
			case 'THEFT':
				// OK
				break;
			default:
				$adj_data->type = 'Reconciliation';
				break;
		}

		$adj_data = json_decode($adj['data']);
		$adj_data = $adj_data->{'@source'};
		// var_dump($adj_data); exit;

		$dtC = new DateTime($adj_data->created_at);
		$dtC->setTimezone($tz0);

		$dtU = new DateTime($adj_data->updated_at);
		$dtU->setTimezone($tz0);

		$rec = [
			$License['code']
			, $adj_data->inventory->id
			, $adj_data->type
			, $adj_data->note
			, sprintf('%0.2f', $adj_data->qty)
			, _date('m/d/Y', $adj_data->created_at)
			, $adj['id']
			, $adj_data->contact->name
			, $dtC->format('m/d/Y')
			, ''
			, ''
			, $cmd
		];

		if ('UPDATE' == $cmd) {
			$rec[9] = $adj_data->contact->name;
			$rec[10] = $dtU->format('m/d/Y');
		}

		$csv_data[] = $rec;

	}

	$row_size = count($csv_data);
	if (0 == $row_size) {
		$status->setPush(202);
	}

	$csv_name = sprintf('InventoryAdjustment_%s_%s.csv', $cre_service_key, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,InventoryExternalIdentifier,AdjustmentReason,AdjustmentDetail,Quantity,AdjustmentDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);

	$csv_temp = fopen('php://temp', 'w');
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}

	// Upload
	// $this->_License
	OpenTHC\Bong\CRE\CCRS\Upload::enqueue($License, $csv_name, $csv_temp);

	$status->setPush(102);

 }
