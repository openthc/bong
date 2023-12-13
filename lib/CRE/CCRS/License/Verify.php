<?php
/**
 * CCRS License Verify Process
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS\License;

class Verify
{
	protected $dbc;

	public $License;

	function __construct($dbc, $License)
	{
		$License['guid'] = $License['code'];
		$this->License = $License;
	}

	// function setForce();

	function verify()
	{
		switch ($this->License['stat']) {
			case 100:
			case 102:
			case 200:
			case 308:
			case 402:
			case 403:
				// Allowed
				break;
			default:
				echo "SKIP: {$this->License['id']}; STAT={$this->License['stat']}\n";
				return(0);
				break;
		}

		$cfg = [
			'server' => \OpenTHC\Config::get('openthc/bong/origin'),
			'company' => \OpenTHC\Config::get('openthc/root/company/id'),
			'contact' => \OpenTHC\Config::get('openthc/root/contact/id'),
			'license' => \OpenTHC\Config::get('openthc/root/license/id')
		];

		$jwt = new \OpenTHC\JWT([
			'iss' => \OpenTHC\Config::get('openthc/bong/id'),
			'exp' => (time() + 120),
			'sub' => $cfg['contact'],
		]);

		$cre = new \OpenTHC\CRE\OpenTHC($cfg);
		$cre->setLicense($this->License);

		$url = sprintf('/license/%s/verify', $this->License['id']);
		// $res = $cre->post($url, []);
		$res = $cre->request('POST', $url, [
			'headers' => [
				// 'authorization' => sprintf('Bearer jwt:%s', $jwt->__toString()),
				'openthc-cre' => 'usa/wa',
				'openthc-jwt' => $jwt->__toString(),
				'openthc-company-id' => $cfg['company'],
				'openthc-license-id' => $cfg['license'],
			]
		]);

		var_dump($res);

		echo $res['code'];
		echo "\t";
		echo __json_encode($res);
		echo "\n";

	}

}
