<?php
/**
 *
 */

namespace OpenTHC\Bong\Traits;

trait UpdateStatus
{
	/**
	 * Updates the Redis Status
	 */
	function updateStatus()
	{
		$rdb = \OpenTHC\Service\Redis::factory();
		$k0 = sprintf('/license/%s', $_SESSION['License']['id']);
		$rdb->hset($k0, sprintf('%s/stat', $this->_tab_name), 100);
		$rdb->hset($k0, sprintf('%s/stat/time', $this->_tab_name), date(\DateTimeInterface::RFC3339));
	}

}
