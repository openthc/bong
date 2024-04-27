<?php
/**
 * Base Case for CCRS
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Test\C_CRE_CCRS;

class Base_Case extends \OpenTHC\Bong\Test\Base
{
	/**
	 *
	 */
	function setup() : void
	{
		// parent::setup();
		$this->cre = $this->getBONGtoCCRS();
	}

}
