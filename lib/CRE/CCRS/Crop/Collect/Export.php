<?php
/**
 * Create Upload for Crop
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\Crop\Collect;

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

	function create($force=false)
	{
		// Check Cache
		$status = new \OpenTHC\Bong\CRE\CCRS\Status($this->_License['id'], 'crop/collect');
		$chk = $status->getStat();
		switch ($chk) {
			case 202:
				return;
		}

		$dbc = _dbc();
		$dbc->query('BEGIN');

		// Get Data
		$arg = [];
		$arg[':l0'] = $this->_License['id'];
		$sql = <<<SQL
		SELECT *
		FROM crop_collect
		WHERE license_id = :l0
		AND stat IN (100, 102, 200, 404, 410)
		ORDER BY id
		LIMIT 1000
		SQL;

		// CSV Data
		$csv_data = [];

		$res_collect = $dbc->fetchAll($sql, $arg);
		foreach ($res_collect as $rec) {

			$src_data = json_decode($rec['data'], true);
			$obj = $src_data['@source'];

			$dtC = new \DateTime($rec['created_at']);
			$dtC->setTimezone($this->_tz0);

			$dtU = new \DateTime($rec['updated_at']);
			$dtU->setTimezone($this->_tz0);

			$PT0 = new \OpenTHC\CRE\CCRS\Product\Type($obj['product']['type']);

			$row = [];
			$row['LicenseNumber'] = $this->_License['code'];
			$row['PlantIdentifier'] = $obj['crop']['id'];
			$row['InventoryExternalIdentifier'] = $obj['inventory']['id'];
			$row['ExternalIdentifier'] = $obj['id']; // Plant-Collect-Plant ID
			$row['InventoryType'] = $PT0->getTypeName();
			$row['CreatedBy'] = '-system-';
			$row['CreatedDate'] = $dtC->format('m/d/Y');
			$row['UpdatedBy'] = '';
			$row['UpdatedDate'] = '';
			$row['Operation'] = '';

			switch ($rec['stat']) {
				case 100:
				case 404:
					$row['Operation'] = 'INSERT';
					$dbc->query('UPDATE crop_collect SET stat = 102 WHERE license_id = :l0 AND id = :s0', [
						':l0' => $this->_License['id'],
						':s0' => $rec['id'],
					]);
					break;
				case 102:
					$row['Operation'] = 'UPDATE';
					$row['UpdatedBy'] = '-system-';
					$row['UpdatedDate'] = $dtU->format('m/d/Y');
					$dbc->query('UPDATE crop_collect SET stat = 200 WHERE license_id = :l0 AND id = :s0', [
						':l0' => $this->_License['id'],
						':s0' => $rec['id'],
					]);
					break;
				case 200:
					$row['Operation'] = 'UPDATE';
					$row['UpdatedBy'] = '-system-';
					$row['UpdatedDate'] = $dtU->format('m/d/Y');
					$dbc->query('UPDATE crop_collect SET stat = 202, data = data #- \'{ "@result" }\' WHERE license_id = :l0 AND id = :s0', [
						':l0' => $this->_License['id'],
						':s0' => $rec['id'],
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
					$row['Operation'] = 'DELETE';
					$dbc->query('UPDATE inventory SET stat = 410202, data = data #- \'{ "@result" }\' WHERE license_id = :l0 AND id = :s0', [
						':l0' => $this->_License['id'],
						':s0' => $rec['id'],
					]);
					break;
				default:
					throw new \Exception("Invalid Inventory Stat '{$rec['stat']}'");
			}

			if (empty($row['Operation'])) {
				// echo "SKIP: {$inv['id']}\n";
				continue;
			}

			$row = array_values($row);
			$csv_data[] = $row;

		}


		// No Data, In Sync
		$row_size = count($csv_data);
		if (0 == $row_size) {
			$dbc->query('ROLLBACK');
			$status->setPush(202);
			return;
		}

		$req_ulid = _ulid();
		$api_code = $this->_cre_config['service-sk'];
		$csv_name = sprintf('Harvest_%s_%s.csv', $api_code, $req_ulid);
		$csv_head = explode(',', 'LicenseNumber,PlantIdentifier,InventoryExternalIdentifier,ExternalIdentifier,InventoryType,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
		$col_size = count($csv_head);

		$csv_temp = fopen('php://temp', 'w');
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', $this->_dt0->format('m/d/Y') ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
		foreach ($csv_data as $row) {
			\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
		}

		fseek($csv_temp, 0);
		$csv_data = stream_get_contents($csv_temp);

		$rec = [];
		$rec['id'] = $req_ulid;
		$rec['license_id'] = $this->_License['id'];
		$rec['name'] = sprintf('CROP_COLLECT UPLOAD %s', $req_ulid);
		$rec['source_data'] = json_encode([
			'name' => $csv_name,
			'data' => $csv_data
		]);

		$output_file = $dbc->insert('log_upload', $rec);
		$dbc->query('COMMIT');

		$status->setPush(102);

		return $req_ulid;

	}
}
