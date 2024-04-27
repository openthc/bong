<?php
/**
 * Test Section Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class CSV_Builder_Test extends \OpenTHC\Bong\Test\Base
{
	protected $_api_code = null;

	function setup() : void
	{
		$cfg = \OpenTHC\CRE::getConfig('usa/wa');
		$this->_api_code = $cfg['service-sk'];
	}

	function test_create_product()
	{
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($this->_api_code, 'product');
		$csv->addRow([ 'LN', '-TEST-', '-TEST-', 'PRODUCT TEST', '', '0', '-TEST-', '-TEST-', date('m/d/Y'), '', '', 'INSERT' ]);

		$tmp = $csv->getName();
		$this->assertMatchesRegularExpression('/^Product_\w{6,10}_\w+.csv$/', $tmp);

		$tmp = $csv->getData();
		// How to Mach?
		$this->assertMatchesRegularExpression('/LicenseNumber,InventoryCategory/', $tmp);

	}

	function test_create_section()
	{
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($this->_api_code, 'section');
		$csv->addRow([
			'LN', 'A', 'Q', 'EI', 'CB', 'CD', 'UB', 'UD', 'O',
		]);

		$tmp = $csv->getName();
		$this->assertMatchesRegularExpression('/^Area_\w{6,10}_\w+.csv$/', $tmp);

		$tmp = $csv->getData();
		$this->assertMatchesRegularExpression('/LicenseNumber,Area/', $tmp);


	}

	function test_create_variety()
	{
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($this->_api_code, 'variety');
		$csv->addRow([ 'LN', "VARIETY TEST", 'HYBRID', '-TEST-', '-TEST-' ]);

		$tmp = $csv->getName();
		$this->assertMatchesRegularExpression('/^Strain_\w{6,10}_\w+.csv$/', $tmp);

		$tmp = $csv->getData();
		// var_dump($tmp);
		// How to Mach?
		$this->assertMatchesRegularExpression('/LicenseNumber,Strain/', $tmp);

	}

}
