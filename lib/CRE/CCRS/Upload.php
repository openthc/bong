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
		$tmp_time = intval($rdb->hget($k0, sprintf('%s/stat/time', $this->_obj)));

		if (empty($tmp_time)) {
			$tmp_stat = 100;
		} else {
			// Make Times and Compare for TTL?
		}

		if ($this->_force) {
			$tmp_stat = 100;
		}

		syslog(LOG_DEBUG, "license:{$this->_lic}/$k1={$tmp_stat}");

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
