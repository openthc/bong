<?php
/**
 * Contact Routes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class Contact extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function() { __exit_text('Not Implemented', 501); });

		// Single
		$a->get('/{id}', function($REQ, $RES, $ARG) {
			if ('current' == $ARG['id']) {
				$ARG['id'] = $_SESSION['Contact']['id'];
			}
			return _from_cre_file('contact/single.php', $REQ, $RES, $ARG);
		});

	}
}
