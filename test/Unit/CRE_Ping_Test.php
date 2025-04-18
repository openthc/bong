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

namespace OpenTHC\Bong\Test\Unit;

class CRE_Ping_Test extends \OpenTHC\Bong\Test\Base
{
	/**
	 * @test
	 */
	public function ping_cre()
	{
		$cre_list = \OpenTHC\CRE::getEngineList();

		foreach ($cre_list as $cre_conf) {

			$cre_conf['company'] = $_ENV['OPENTHC_TEST_COMPANY_ID'];
			$cre_conf['contact'] = $_ENV['OPENTHC_TEST_CONTACT_ID'];
			$cre_conf['license'] = $_ENV['OPENTHC_TEST_LICENSE_ID'];
			$cre_conf['license-key'] = $_ENV['OPENTHC_TEST_LICENSE_SK'];
			$cre_conf['service'] = $_ENV['OPENTHC_TEST_CLIENT_SERVICE_ID'];
			$cre_conf['service-sk'] = $_ENV['OPENTHC_TEST_CLIENT_SERVICE_SK'];

			$cre = \OpenTHC\CRE::factory($cre_conf);
			$this->assertNotEmpty($cre);
			$this->assertTrue($cre instanceof \OpenTHC\CRE\Base);

			$arg = [
				'id' => $cre_conf['license'],
				'code' => $_ENV['OPENTHC_TEST_LICENSE_CODE'],
				'guid' => $_ENV['OPENTHC_TEST_LICENSE_CODE'],
				'sk' => $cre_conf['license-key']
			];
			$res = $cre->setLicense($arg);
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
