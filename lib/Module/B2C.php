<?php
/**
 *
 */

namespace OpenTHC\Bong\Module;

class B2C extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function() { __exit_text('Not Implemented', 501); });
	}
}
