<?php
/**
 *
 */

namespace OpenTHC\Bong\Module;

class Auth extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->map(['GET','POST'], '/open', '\OpenTHC\Bong\Controller\Auth\Open');
		$a->map(['GET','POST'], '/ping', function($REQ, $RES, $ARG) {
			// return _from_cre_file('ping.php', $RES, $ARG);
			$ret = [
				'data' => $_SESSION,
				'meta' => [],
			];
			__ksort_r($ret);
			return $RES->withJSON($ret, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		});
		$a->get('/shut', 'OpenTHC\Controller\Auth\Shut');

	}

}
