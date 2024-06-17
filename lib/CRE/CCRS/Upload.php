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
		$max_age = 60 * 60 * 8; // 8 hours
		switch ($tmp_stat) {
		case 102:
			$max_age = 60 * 30; // 30 minutes
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
