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
		$this->_force = $cfg['force'];
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

		// switch ($rdb_stat) {
		// 	case 200:
		// 		// $rdb_stat = 202;
		// 		break;
		// 	case 202:
		// 		// All Good
		// 		return(0);
		// }

		return $rdb_stat;
	}

	/**
	 *
	 */
	function setStatus($s)
	{
		$rdb = \OpenTHC\Service\Redis::factory();
		$rdb->hset(sprintf('/license/%s', $this->_lic), sprintf('%s/stat', $this->_obj), $s);
		$rdb->hset(sprintf('/license/%s', $this->_lic), sprintf('%s/stat/time', $this->_obj), time());
	}
}
