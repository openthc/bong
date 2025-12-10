<?php
/**
 * Create Upload for Inventory
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Inventory;

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
		$status = new \OpenTHC\Bong\CRE\CCRS\Status($this->_License['id'], 'inventory');
		$chk = $status->getStat();
		switch ($chk) {
			case 202:
				return;
		}

		$dbc = _dbc();

		// Get Data
		$arg = [];
		$arg[':l0'] = $this->_License['id'];
		$sql = <<<SQL
		SELECT *
		FROM inventory
		WHERE license_id = :l0
		AND stat IN (100, 102, 200, 404, 410)
		ORDER BY id
		SQL;

		// CSV Data
		$csv_data = [];

		$res_inventory = $dbc->fetchAll($sql, $arg);
		foreach ($res_inventory as $inv) {

			$inv_data = json_decode($inv['data'], true);
			$inv_source = $inv_data['@source'];

			if (empty($inv_data['@version'])) {
				throw new \Exception('Missing @version [CUI-065]');
				$dbc->query('UPDATE inventory SET stat = 400 where id = :i0', [
					':i0' => $inv['id'],
				]);

				continue;
			}

			$inv_source['cost'] = floatval($inv_source['cost']);
			$inv_source['qty'] = floatval($inv_source['qty']);

			$dtC = new \DateTime($inv['created_at']);
			$dtC->setTimezone($this->_tz0);

			$dtU = new \DateTime($inv['updated_at']);
			$dtU->setTimezone($this->_tz0);

			$cmd = '';
			switch ($inv['stat']) {
				case 100:
				case 404:
					$cmd = 'INSERT';
					$dbc->query('UPDATE inventory SET stat = 102 WHERE id = :s0', [
						':s0' => $inv['id'],
					]);
					break;
				case 102:
					$cmd = 'UPDATE';
					$dbc->query('UPDATE inventory SET stat = 200 WHERE id = :s0', [
						':s0' => $inv['id'],
					]);
					break;
				case 200:
					$cmd = 'UPDATE';
					$dbc->query('UPDATE inventory SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $inv['id'],
					]);
					break;
				case 400:
					// Ignore
					// $cmd = 'UPDATE';
					// $dbc->query('UPDATE inventory SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
					// 	':s0' => $inv['id'],
					// ]);
					break;
				case 410:
					$cmd = 'DELETE';
					$dbc->query('UPDATE inventory SET stat = 410202, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $inv['id'],
					]);
					break;
				default:
					throw new \Exception("Invalid Inventory Stat '{$inv['stat']}'");
			}

			if (empty($cmd)) {
				// echo "SKIP: {$inv['id']}\n";
				continue;
			}

			if ($inv_source['qty'] < 0) {
				$inv_source['qty'] = 0;
			}

			// Eight Decimal Places Limit (IR73322 & IR73481)
			if ($inv_source['qty_initial'] > 99999999) {
				echo "SKIPPING INVENTORY {$inv['guid']}; qty_initial: {$inv_source['qty_initial']}\n";
				continue;
			}

			if ($inv_source['qty'] > 99999999) {
				echo "SKIPPING INVENTORY {$inv['guid']}; qty: {$inv_source['qty']}\n";
				continue;
			}

			$qty_max = [];
			$qty_max[] = floatval($inv_source['qty_initial']);
			$qty_max[] = floatval($inv_source['qty']);
			$qty_max = max($qty_max);

			// Insert
			$row = [
				$this->_License['code']
				, \OpenTHC\CRE\CCRS::sanatize($inv_source['variety']['name'], 100)
				, \OpenTHC\CRE\CCRS::sanatize($inv_source['section']['name'], 50)
				, \OpenTHC\CRE\CCRS::sanatize($inv_source['product']['name'], 75)
				, $qty_max // sprintf('%0.2f', $inv_source['qty_initial']) // InitialQuantity
				, sprintf('%0.2f', $inv_source['qty'])
				, $inv_source['cost'] ?: '0.01'  // New Default
				, 'FALSE'
				, $inv['id']
				, '-system-'
				, $dtC->format('m/d/Y')
				, '' // '-system-'
				, '' // $dtU->format('m/d/Y')
				, $cmd
			];

			// Add Contact ULID?
			switch ($cmd) {
				case 'DELETE':
				case 'UPDATE':
					$row[11] = '-system-';
					$row[12] = $dtU->format('m/d/Y');
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
		$api_code = $this->_cre_config['service-sk'];
		$csv_name = sprintf('Inventory_%s_%s.csv', $api_code, $req_ulid);
		$csv_head = explode(',', 'LicenseNumber,Strain,Area,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
		$col_size = count($csv_head);

		$csv_temp = fopen('php://temp', 'w');
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', $this->_dt0->format('m/d/Y') ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
		foreach ($csv_data as $row) {
			\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
		}

		\OpenTHC\Bong\CRE\CCRS\Upload::enqueue($this->_License, $csv_name, $csv_temp);

		$status->setPush(102);

		return $req_ulid;


	}

}
