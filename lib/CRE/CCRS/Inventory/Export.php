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
		$this->_cre_config = \OpenTHC\CRE::getConfig('usa/wa');
		$this->_tz0 = new \DateTimezone($this->_cre_config['tz']);
		$this->_dt0 = new \DateTime('now', $this->_tz0);
	}

	/**
	 *
	 */
	function create($force=false)
	{
		// Check Cache
		$uphelp = new \OpenTHC\Bong\CRE\CCRS\Upload([
			'license' => $this->_License['id'],
			'object' => 'inventory',
			'force' => $force
		]);
		if (202 == $uphelp->getStatus()) {
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
				// wtf /mbw 2023-142
				// var_dump($inv);
				// throw new \Exception('Missing @version [CUI-065]');
				$dbc->query('UPDATE inventory SET stat = 400 where id = :i0', [
					':i0' => $inv['id'],
				]);

				continue;
			}

			$dtC = new DateTime($inv['created_at']);
			$dtC->setTimezone($this->_tz0);

			$dtU = new DateTime($inv['updated_at']);
			$dtU->setTimezone($this->_tz0);

			$cmd = '';
			switch ($inv['stat']) {
				case 100:
				case 404:
					$cmd = 'INSERT';
					$dbc->query('UPDATE inventory SET stat = 102, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $inv['id'],
					]);
					break;
				case 102:
					// Skip, 102 should resolve to a 200 or 400 level response
					// $cmd = 'INSERT';
					break;
				case 200:
					$cmd = 'UPDATE';
					$dbc->query('UPDATE inventory SET stat = 202, data = data #- \'{ "@result" }\' WHERE id = :s0', [
						':s0' => $inv['id'],
					]);
					break;
				case 202:
					// Fully Uploaded
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
				case 666:
					// $cmd = 'DELETE';
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

			// Insert
			$row = [
				$License['code']
				, \OpenTHC\CRE\CCRS::sanatize($inv_source['variety']['name'], 100)
				, \OpenTHC\CRE\CCRS::sanatize($inv_source['section']['name'], 50)
				, \OpenTHC\CRE\CCRS::sanatize($inv_source['product']['name'], 75)
				, sprintf('%0.2f', $inv_source['qty_initial'])
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
			$uphelp->setStatus(202);
			return;
		}

		$req_ulid = _ulid();
		$cre_service_key = $this->_cre_config['service-sk'];
		$csv_name = sprintf('Inventory_%s_%s.csv', $cre_service_key, $req_ulid);
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

		OpenTHC\Bong\CRE\CCRS\Upload::enqueue($License, $csv_name, $csv_temp);

		$uphelp->setStatus(102);

		return $req_ulid;


	}

}
