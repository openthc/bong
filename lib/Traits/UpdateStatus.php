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
		$rdb->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/stat', $this->_tab_name), 100);
		$rdb->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/stat/time', $this->_tab_name), time());
	}

}
