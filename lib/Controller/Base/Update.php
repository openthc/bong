<?php
/**
 * Update Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Update extends \OpenTHC\Controller\Base
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
		$R = \OpenTHC\Service\Redis::factory();
		$R->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/stat', $this->_tab_name), 100);
		$R->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/stat/time', $this->_tab_name), time());
		$R->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/sync', $this->_tab_name), 100);
	}

	/**
	 *
	 */
	function verifyRequest()
	{

	}

}
