<?php
/**
 * App System Controller
 */

namespace OpenTHC\Bong\Controller;

use OpenTHC\CRE;

class System extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES, $ARG)
	{
		// Return a list of supported CREs
		$cre_list = CRE::getEngineList();

		ob_start();

		echo "Supporting the Following Systems:\n";
		echo "\n";

		foreach ($cre_list as $cre_code => $cre_info) {

			echo sprintf('% -15s', $cre_code);
			echo $cre_info['server'];
			echo "\n";

		}

		$output_text = ob_get_clean();

		__exit_text($output_text);
	}

	/**
	 * Ping all the endpoints
	 */
	function ping($REQ, $RES, $ARG)
	{
		// Return a list of supported CREs
		$cre_list = CRE::getEngineList();

		$cfg = array(
			'headers' => array(
				'user-agent' => 'OpenTHC/420.18.230 (BONG)',
			),
			'http_errors' => false
		);

		$c = new \GuzzleHttp\Client($cfg);

		$req_list = array();

		$cre_link_map = [];
		foreach ($cre_list as $cre_info) {
			$url = $cre_info['server'];
			if (!empty($url)) {
				$req_list[$url] = $c->getAsync($url);
				$cre_link_map[$url] = $cre_info;
			}
		}

		$res_list = \GuzzleHttp\Promise\settle($req_list)->wait();

		foreach ($res_list as $key => $res) {

			$cre_info = $cre_link_map[$key];

			echo '<pre>';
			echo "Connect: {$cre_info['id']} @ $key<br>";

			switch ($res['state']) {
			case 'fulfilled':

				$res = $res['value'];
				$c = $res->getStatusCode();

				echo "$c<br>";
				echo '<pre>';
				// var_dump(get_class_methods($res));
				// var_dump($res->getHeaders());
				// echo h($res->getBody());
				echo '</pre>';
				break;

			case 'rejected':
				// Problem
				break;
			}

		}

		exit(0);
	}

	function license_type($REQ, $RES, $ARG)
	{
		// var_dump($_SESSION);
		return _from_cre_file('license-type.php', $RES, $ARG);
	}

	function product_type($REQ, $RES, $ARG)
	{
		// var_dump($_SESSION);
		return _from_cre_file('product-type.php', $RES, $ARG);
	}

}
