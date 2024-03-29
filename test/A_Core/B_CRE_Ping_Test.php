<?php
/**
 * Notes about the Auth module
 * The "program-key" cooresponds to a code that is a company object identifier
 * The "license-key" cooresponds to a code that is a license object identifier
 *
 * Licenses can belong to a company in a 1:M way
 * Companies can have different permissions to act on a license's object
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\A_Core;

class B_CRE_Ping_Test extends \OpenTHC\Bong\Test\Base_Case
{
	/**
	 * @test
	 */
	public function ping_cre()
	{
		$cre_list = \OpenTHC\CRE::getEngineList();

		foreach ($cre_list as $cre_conf) {

			$cre_conf['company'] = getenv('OPENTHC_TEST_COMPANY_ID');
			$cre_conf['contact'] = getenv('OPENTHC_TEST_CONTACT_ID');
			// $cre_conf['license'] = getenv('OPENTHC_TEST_LICENSE');
			$cre_conf['license'] = getenv('OPENTHC_TEST_LICENSE_ID');
			$cre_conf['license-key'] = getenv('OPENTHC_TEST_LICENSE_SECRET');
			$cre_conf['service'] = getenv('OPENTHC_TEST_BASE_SERVICE_ID');
			// $cre_conf['service-key'] = getenv('OPENTHC_TEST_BASE_SERVICE_SK');
			$cre_conf['service-sk'] = getenv('OPENTHC_TEST_BASE_SERVICE_SK');

			$cre = \OpenTHC\CRE::factory($cre_conf);
			$this->assertNotEmpty($cre);
			$this->assertTrue($cre instanceof \OpenTHC\CRE\Base);

			$res = $cre->setLicense($cre_conf['license']);
			$res = $cre->ping();

			$this->assertIsArray($res);
			$this->assertCount(3, $res, sprintf('CRE: "%s"', $cre_conf['id']));
			$this->assertArrayHasKey('code', $res);
			$this->assertArrayHasKey('data', $res, sprintf('Engine: %s', $cre_conf['id']));
			$this->assertArrayHasKey('meta', $res, sprintf('Engine: %s', $cre_conf['id']));

			break;
		}
	}

}
