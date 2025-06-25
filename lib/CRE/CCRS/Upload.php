<?php
/**
 * CCRS CSV Helper
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS;

class Upload
{
	protected bool $_force = false;

	protected string $_lic;

	protected string $obj;

	/**
	 * Use BONG API to Insert Outgoing CSV
	 */
	static function enqueue(array $License, string $csv_name, $csv_data)
	{
		// If a Stream, then Rewind
		if (is_resource($csv_data)) {
			fseek($csv_data, 0);
		}

		$url_base = \OpenTHC\Config::get('openthc/bong/origin');

		$cfg = array(
			'base_uri' => $url_base,
			'allow_redirects' => false,
			'cookies' => false,
			'http_errors' => false,
			'verify' => false,
		);
		$api_bong = new \GuzzleHttp\Client($cfg);

		$arg = [
			'headers' => [
				'content-name' => basename($csv_name),
				'content-type' => 'text/csv',
				'openthc-company' => $License['company_id'], // v0
				'openthc-company-id' => $License['company_id'], // v1
				'openthc-license' => $License['id'], // v0
				'openthc-license-id' => $License['id'], // v1
				'openthc-license-code' => $License['code'],
				'openthc-license-name' => $License['name'],
				'openthc-disable-update' => true,
			],
			'body' => $csv_data // this resource is closed by Guzzle
		];

		if ( ! empty($_SERVER['argv'])) {
			$argv = implode(' ', $_SERVER['argv']);
			if (strpos($argv, '--dump')) {
				if (is_resource($arg['body'])) {
					$arg['body'] = stream_get_contents($arg['body']);
				}
				var_dump($arg['headers']);
				echo ">>>\n{$arg['body']}###\n";
				return;
			}
		}

		$res = $api_bong->post('/upload/outgoing', $arg);

		$ret = [];
		$ret['code'] = $res->getStatusCode();
		$ret['data'] = $res->getBody()->getContents();

		switch ($ret['code']) {
		case 200:
		case 201:
			// OK
			break;
		default:
			throw new \Exception('Invalid Response from BONG on Upload');
		}

		return $ret;
	}

	/**
	 *
	 */
	function __construct($cfg)
	{
		$this->_force = (bool)$cfg['force'];
		$this->_lic = $cfg['license'];
		$this->_obj = $cfg['object'];
	}

	/**
	 *
	 */
	function getStatus()
	{
		$rdb = \OpenTHC\Service\Redis::factory();
		$k0 = sprintf('/license/%s/%s/stat', $this->_lic, $this->_obj);

		$k0 = sprintf('/license/%s', $this->_lic);
		$k1 = sprintf('%s/stat', $this->_obj);

		$tmp_stat = intval($rdb->hget($k0, sprintf('%s/stat', $this->_obj)));
		$tmp_time = $rdb->hget($k0, sprintf('%s/stat/time', $this->_obj));

		if (empty($tmp_time)) {
			$tmp_stat = 100;
		}

		$max_age = 86400; // 24 hours
		switch ($tmp_stat) {
		case 102:
			// $max_age = 60 * 30;  // 30 minutes
			// $max_age = 60 * 60;  // 60 minutes
			// $max_age = 60 * 120; // 120 minutes
			// $max_age = 60 * 60 * 4; // 240 minutes, 4h gap for Re-Upload
			$max_age = 60 * 60 * 8; // 8 hours -- CCRS keeps getting slower /djb 2025-05-05
			break;
		}

		$age = 0;
		$t0 = time();
		$t1 = strtotime($tmp_time);
		$age = $t0 - $t1;
		if ($age > $max_age) {
			$tmp_stat = 100;
		}

		if ($this->_force) {
			$tmp_stat = 100;
		}

		syslog(LOG_DEBUG, "license:{$this->_lic}/$k1={$tmp_stat};age=$age");

		return $tmp_stat;
	}

	/**
	 *
	 */
	function setStatus($s)
	{
		$rdb = \OpenTHC\Service\Redis::factory();
		$k0 = sprintf('/license/%s', $this->_lic);
		$rdb->hset($k0, sprintf('%s/stat', $this->_obj), $s);
		$rdb->hset($k0, sprintf('%s/stat/time', $this->_obj), date(\DateTimeInterface::RFC3339));
	}
}
