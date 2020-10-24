<?php
/**
 * App Base Controller
 */

namespace OpenTHC\Bong\Controller;

class Browse extends \OpenTHC\Bong\Controller\Base
{
	function __invoke($REQ, $RES, $ARG)
	{
		session_write_close();

		$data = [];
		$data['Page'] = [ 'title' => 'Browse' ];
		$data['cre_auth'] = $_SESSION['cre-auth'];
		$data['cre_meta_license'] = $_SESSION['cre-auth']['license'];

		return $this->render($RES, 'browse.php', $data);

	}
}
