<?php
/**
 *
 */

namespace OpenTHC\Bong\Module;

class Company extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function() { __exit_text('Not Implemented', 501); });
	}
}
