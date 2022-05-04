<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Contact extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function() { __exit_text('Not Implemented', 501); });
	}
}
