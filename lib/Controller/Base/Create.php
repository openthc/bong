<?php
/**
 * Create Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Create extends \OpenTHC\Controller\Base
{
	protected $_tab_name;

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		if (empty($this->_tab_name)) {
			__exit_text('Invalid Incantation [CBS-020]', 500);
		}

	}

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
