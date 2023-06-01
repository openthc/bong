<?php
/**
 * Test Section Interface
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class CSV_Builder_Test extends \OpenTHC\Bong\Test\Base_Case
{
	protected $_api_code = null;

	function setup() : void
	{
		$this->_api_code = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	}

	function test_create_product()
	{
		$csv = new \OpenTHC\Bong\CRE\CCRS\CSV($this->_api_code, 'product');
		$csv->addRow([ '-canary-', '-canary-', '-canary-', 'PRODUCT TEST', '', '0', '-canary-', '-canary-', date('m/d/Y'), '', '', 'INSERT' ]);

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
		$csv->addRow([ '-canary-', "VARIETY TEST", '-canary-', '-canary-', '-canary-' ]);

		$tmp = $csv->getName();
		$this->assertMatchesRegularExpression('/^Strain_\w{6,10}_\w+.csv$/', $tmp);

		$tmp = $csv->getData();
		// var_dump($tmp);
		// How to Mach?
		$this->assertMatchesRegularExpression('/LicenseNumber,Strain/', $tmp);

	}

}
