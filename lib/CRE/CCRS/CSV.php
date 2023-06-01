<?php
/**
 * CCRS CSV Helper
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS;

class CSV
{
	protected $_api_code;

	protected $_col_list;

	protected $_col_size;

	protected $_req_data;

	protected $_csv_data;

	protected $_csv_head;

	protected $_csv_type;

	protected $_csv_ulid;

	/**
	 *
	 */
	function __construct(string $api_code, string $csv_type)
	{
		$this->_api_code = $api_code;
		$this->_csv_ulid = _ulid();

		$this->initFileType($csv_type);
		// $this->_col_list = $this->getColumnList();

		if (empty($this->_csv_type)) {
			throw new \Exception('Invalid CSV File Type [CCC-039]');
		}

		if (empty($this->_col_list)) {
			throw new \Exception('Invalid CSV File Type [CCC-039]');
		}

	}

	/**
	 *
	 */
	function addRow(array $row) : int
	{
		$c = count($row);
		if ($c != $this->_col_size) {
			throw new \Exception('Rows must match column count [CCC-054]');
		}

		$this->_csv_data[] = $row;

		return count($this->_csv_data);
	}

	/**
	 * @param string $return_type 'string' or 'stream'
	 * @return string|resource
	 */
	function getData($return_type='string')
	{
		$row_size = count($this->_csv_data) + 1;

		$csv_temp = fopen('php://temp', 'w');

		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($this->_col_list));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $this->_req_data);
		foreach ($this->_csv_data as $row) {
			\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
		}

		// Upload
		fseek($csv_temp, 0);

		if ('stream' == $return_type) {
			return $csv_temp;
		}

		return stream_get_contents($csv_temp);

	}

	// function getFile()

	function getName() : string
	{
		return sprintf('%s_%s_%s.csv', $this->_csv_type, $this->_api_code, $this->_csv_ulid);
	}

	/**
	 *
	 */
	function initFileType($t) : void
	{
		switch (strtoupper($t)) {
			case 'B2B/INCOMING':
				$this->_csv_type = 'InventoryTransfer';
				$this->_col_list = explode(',', 'FromLicenseNumber,ToLicenseNumber,FromInventoryExternalIdentifier,ToInventoryExternalIdentifier,Quantity,TransferDate,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
				$this->_req_data = [ '-canary-', '-canary-', "B2B_INCOMING UPLOAD $req_ulid", '-canary-', 0, date('m/d/Y'), '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
				break;
			case 'B2B/OUTGOING': // OpenTHC Name
				$this->_csv_type = 'Sale';
				$this->_col_list = explode(',', 'LicenseNumber,SoldToLicenseNumber,InventoryExternalIdentifier,PlantExternalIdentifier,SaleType,SaleDate,Quantity,UnitPrice,Discount,SalesTax,OtherTax,SaleExternalIdentifier,SaleDetailExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
				$this->_req_data = [ '-canary-', '-canary-', "B2B_OUTGOING UPLOAD $req_ulid", '-canary-', '0', '', '-canary-', '-system-', date('m/d/Y'), '', '', 'UPDATE' ];
				break;
			// case 'B2B/OUTGOING/NOTICE': // OpenTHC Name
			// case 'MANIFEST': // CCRS Name
			// 	$this->_csv_type = 'Manifest';
			// 	$this->_col_list =
			// 	$this->_req_data =
			// 	break;
			// case 'B2C': // OpenTHC Name
			// case 'SALES': // CCRS Name
			// 	break;
			case 'CROP':
			case 'PLANT':
				$this->_csv_type = 'Plant';
				$this->_col_list = explode(',', 'LicenseNumber,PlantIdentifier,Area,Strain,PlantSource,PlantState,GrowthStage,MotherPlantExternalIdentifier,HarvestDate,IsMotherPlant,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
				$this->_req_data = [ '-canary-', "CROP UPLOAD $this->_csv_ulid", '-canary-', '-canary-', '-canary-', '-canary-', '-canary-', '-canary-', date('m/d/Y'), 'FALSE', '-canary-', 'OpenTHC', date('m/d/Y'), '' ,'', 'UPDATE' ];
				break;
			case 'INVENTORY':
				$this->_csv_type = 'Inventory';
				$this->_col_list = explode(',', 'LicenseNumber,Strain,Area,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
				$this->_req_data = [ '-canary-', '-canary-', '-canary-', '-canary-', '0', '0', '0', 'FALSE', "INVENTORY UPLOAD $req_ulid", '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
				break;
			case 'INVENTORY/ADJUST':
				$this->_csv_type = 'InventoryAdjustment';
				$this->_col_list = explode(',', 'LicenseNumber,Strain,Area,Product,InitialQuantity,QuantityOnHand,TotalCost,IsMedical,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
				$this->_req_data = [ '-canary-', '-canary-', '-canary-', '-canary-', '0', '0', '0', 'FALSE', "INVENTORY UPLOAD $req_ulid", '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
				break;
			case 'PRODUCT':
				$this->_csv_type = 'Product';
				$this->_col_list = explode(',', 'LicenseNumber,InventoryCategory,InventoryType,Name,Description,UnitWeightGrams,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
				$this->_req_data = [ '-canary-', '-canary-', '-canary-', "PRODUCT UPLOAD $req_ulid", '', '0', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
				break;
			case 'SECTION':
			case 'AREA':
				$this->_csv_type = 'Area';
				$this->_col_list = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
				$this->_req_data = [ '-canary-', "SECTION UPLOAD $this->_csv_ulid", 'FALSE', '-canary-', '-canary-', date('m/d/Y'), '' ,'', 'DELETE' ];
				break;
			case 'VARIETY':
			case 'STRAIN':
				$this->_csv_type = 'Strain';
				$this->_col_list = explode(',', 'LicenseNumber,Strain,StrainType,CreatedBy,CreatedDate');
				$this->_req_data = [ '-canary-', "VARIETY UPLOAD $req_ulid", '-canary-', '-canary-', '-canary-' ];
				break;
			default:
				throw new \Exception(sprintf('Invalid CSV Type "%s" [CCC-155]', $t));
		}

		$this->_col_size = count($this->_col_list);

	}

	function isEmpty()
	{
		return (0 == count($this->_csv_data));
	}
}
