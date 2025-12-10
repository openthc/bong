<?php
/**
 * CCRS Status Helper
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE\CCRS;

class Status
{
	protected $lic;

	protected $obj;

	protected $rdb;

	function __construct(string $lic, string $obj)
	{
		$this->lic = $lic;
		$this->obj = $obj;
		$this->rdb = \OpenTHC\Service\Redis::factory();
	}

	function getStat()
	{
		// $rdb = \OpenTHC\Service\Redis::factory();
		$key = sprintf('/license/%s/%s/stat', $this->_lic, $this->_obj);

		// $k0 = sprintf('/license/%s', $this->_lic);
		// $k1 = sprintf('%s/stat', $this->_obj);

		// $tmp_stat = intval($rdb->hget($k0, sprintf('%s/stat', $this->_obj)));
		// $tmp_time = $rdb->hget($k0, sprintf('%s/stat/time', $this->_obj));

		// if (empty($tmp_time)) {
		// 	$tmp_stat = 100;
		// }

		// // $max_age = 86400; // 24 hours
		// // switch ($tmp_stat) {
		// // case 102:
		// // 	// $max_age = 60 * 30;  // 30 minutes
		// 	$max_age = 60 * 60;  // 60 minutes
		// // 	// $max_age = 60 * 120; // 120 minutes
		// // 	// $max_age = 60 * 60 * 4; // 240 minutes, 4h gap for Re-Upload
		// // 	$max_age = 60 * 60 * 8; // 8 hours -- CCRS keeps getting slower /djb 2025-05-05
		// // 	break;
		// // }

		// $age = 0;
		// $t0 = time();
		// $t1 = strtotime($tmp_time);
		// $age = $t0 - $t1;
		// if ($age > $max_age) {
		// 	$tmp_stat = 100;
		// }

		// if ($this->_force) {
		// 	$tmp_stat = 100;
		// }

	}

	function setData($s)
	{
		$time = date(\DateTimeInterface::RFC3339);

		// v0
		$k0 = sprintf('/license/%s', $this->lic);
		$this->rdb->hset($k0, sprintf('%s/stat', $this->obj), 100);
		$this->rdb->hset($k0, sprintf('%s/stat/time', $this->obj), $time);


		// v1
		$k0 = sprintf('/license/%s', $this->lic);
		$key = sprintf('/bong/license/%s/%s/stat', $this->lic, $this->obj);
		$this->rdb->set($key, $s, [ 'ex' => 14400 ]);

		$key = sprintf('/bong/license/%s/%s/stat/time', $this->lic, $this->obj);
		$this->rdb->set($key, $s, [ 'ex' => 14400 ]);

		// $this->rdb->hset($k0, sprintf('%s/stat', $this->obj), $s);
		// $this->rdb->hset($k0, sprintf('%s/stat/time', $this->obj), $time);

	}

	function setPull($stat)
	{
		$time = date(\DateTimeInterface::RFC3339);

		$key = sprintf('/bong/license/%s/%s/pull', $this->lic, $this->obj);
		// echo "Set: $key = $stat at $time\n";
		$this->rdb->set($key, $stat, [ 'ex' => 14400 ]);

		$key = sprintf('/bong/license/%s/%s/pull/time', $this->lic, $this->obj);
		$this->rdb->set($key, $time, [ 'ex' => 14400 ]);

		// Set Global
		$this->setData($stat);

		// Legacy HashMap
		$key = sprintf('/license/%s', $this->lic);
		$this->rdb->hset($key, sprintf('%s/pull', $this->obj), $stat);
		$this->rdb->hset($key, sprintf('%s/pull/time', $this->obj), $time);

	}

	function setPush($stat)
	{
		$time = date(\DateTimeInterface::RFC3339);

		// v0
		$this->rdb->hset(sprintf('/license/%s', $this->lic), sprintf('%s/push', $this->obj), $stat);
		$this->rdb->hset(sprintf('/license/%s', $this->lic), sprintf('%s/push/time', $this->obj), $time);

		// v1
		$key = sprintf('/bong/license/%s/%s/push', $this->lic, $this->obj);
		$this->rdb->set($key, $stat, [ 'ex' => 14400 ]);

		$key = sprintf('/bong/license/%s/%s/push/time', $this->lic, $this->obj);
		$this->rdb->set($key, $time, [ 'ex' => 14400 ]);

		// Set Global
		$this->setData($stat);

	}

}
