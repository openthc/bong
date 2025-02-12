<?php
/**
 * BioTrack2024 Interface
 */

namespace OpenTHC\Bong\CRE;

class BioTrack2024 extends \OpenTHC\Bong\CRE\Base
{
	protected $base_url = 'https://licensee-api.sandbox-ny.biotr.ac/';

	function __construct($cfg)
	{

	}

	/**
	 *
	 */
	function auth()
	{
		$head = [];
		$head['X-Ubi'] = $this->Company->guid;
		$head['X-License'] = $this->License->code;
		$head['X-Session-Token'] = null;
		$res = $this->client->post('/v1/user/login', $body, $head);

		return $res;
	}

	/**
	 *
	 */
	function crop()
	{
		return new BioTrack2024\Crop($this);
	}

	/**
	 *
	 */
	function section()
	{
		return new BioTrack2024\Section($this);
	}


}
