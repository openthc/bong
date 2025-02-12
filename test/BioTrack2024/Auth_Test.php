<?php
/**
 */

namespace OpenTHC\Bong\Test\BioTrack2024;

class Auth_Test extends \OpenTHC\Bong\Test\Base
{
	function test_auth()
	{
		$cfg = [];
		$api = new \OpenTHC\Bong\CRE\BioTrack2024($cfg);
		$res = $api->auth();
	}

}
