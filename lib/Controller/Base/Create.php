<?php
/**
 * Create Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Create extends \OpenTHC\Controller\Base
{
	use \OpenTHC\Bong\Traits\GetReturnObject;
	use \OpenTHC\Bong\Traits\UpdateStatus;

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

}
