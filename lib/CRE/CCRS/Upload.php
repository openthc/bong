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
		$k0 = sprintf('/license/%s', $this->_lic);
		$k1 = sprintf('%s/stat', $this->_obj);

		$rdb_stat = intval($rdb->hget($k0, $k1));
		if ($this->_force) {
			$rdb_stat = 100;
		}

		syslog(LOG_DEBUG, "license:{$this->_lic}/$k1={$rdb_stat}");

		return $rdb_stat;
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

		$k0 = sprintf('/license/%s/%s', $this->_lic, $this->_obj);
		$rdb->set(sprintf('%s/stat', $k0), $s, [ 'ex' => 3600 ]);
		$rdb->set(sprintf('%s/stat/time', $k0), date(\DateTimeInterface::RFC3339), [ 'ex' => 3600 ]);

	}
}
