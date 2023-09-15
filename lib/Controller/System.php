<?php
/**
 * App System Controller
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller;

use OpenTHC\CRE;

class System extends \OpenTHC\Controller\Base
{
	/**
	 *
	 */
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
	 *
	 */
	function ajax($REQ, $RES, $ARG)
	{
		session_write_close();

		switch ($_GET['a']) {
			case 'request-processing':
				return $this->stat_request_queue($RES);
			case 'recent-update-stat':
				return $this->stat_recent_update($RES);
			case 'stat-upload':
				return $this->stat_upload($RES);
		}

		$html = [];
		$html[] = '<h2 class="alert alert-warning">Unknown Request</h2>';

		$html[] = '<h3>GET</h3>';
		$html[] = '<pre>';
		$html[] = json_encode($_GET, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$html[] = '</pre>';

		$html[] = '<h3>POST</h3>';
		$html[] = '<pre>';
		$html[] = json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$html[] = '</pre>';

		return $RES->write(implode('', $html));

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

	/**
	 *
	 */
	function status($REQ, $RES, $ARG)
	{

		$data = [];

		$data['Page'] = [ 'title' => 'Data Status' ];

		return $RES->write( $this->render('system/status.php', $data) );

	}

	function license_type($REQ, $RES, $ARG)
	{
		// var_dump($_SESSION);
		return _from_cre_file('license-type.php', $REQ, $RES, $ARG);
	}

	/**
	 *
	 */
	function product_type($REQ, $RES, $ARG)
	{
		// var_dump($_SESSION);
		return _from_cre_file('product-type.php', $REQ, $RES, $ARG);
	}

	/**
	 *
	 */
	function stat_request_queue($RES)
	{
		$dt0 = new \DateTime();

		$path_outgoing = sprintf('%s/var/ccrs-outgoing/*.csv', APP_ROOT);
		$path_incoming = sprintf('%s/var/ccrs-incoming/*.csv', APP_ROOT);
		$path_incoming_mail = sprintf('%s/var/ccrs-incoming-mail/*', APP_ROOT);
		$path_incoming_done = sprintf('%s/var/ccrs-incoming-done/*.csv', APP_ROOT);
		$path_incoming_fail = sprintf('%s/var/ccrs-incoming-fail/*.csv', APP_ROOT);

		$R = \OpenTHC\Service\Redis::factory();
		$last_incoming_diff = null;
		$last_incoming_time = $R->get('/cre/ccrs/incoming');
		if ( ! empty($last_incoming_time)) {
			$dt1 = new \DateTime($last_incoming_time);
			$dtDiff = $dt0->diff($dt1);
			$last_incoming_diff = $dtDiff->format('%aD%Hh%Im%Ss');
		}

		$cre_stat = $R->hgetall('/cre/ccrs');


		$out = [];
		$out[] = '<pre>';
		$out[] = $dt0->format(\DateTimeInterface::RFC3339);
		$out[] = sprintf('CCRS Outgoing:      %04d', count(glob($path_outgoing)));
		$out[] = sprintf('CCRS Incoming:      %04d  -- @%s [%s]', count(glob($path_incoming)), $last_incoming_time, $last_incoming_diff);
		$out[] = sprintf('CCRS Incoming Mail: %04d', count(glob($path_incoming_mail)));
		$out[] = sprintf('CCRS Incoming/Done: %04d', count(glob($path_incoming_done)));
		$out[] = sprintf('CCRS Incoming/Fail: %04d', count(glob($path_incoming_fail)));
		$out[] = 'CRE HGET:';
		$out[] = print_r($cre_stat, true);
		$out[] = '</pre>';

		return $RES->write(implode("\n", $out));
	}

	/**
	 *
	 */
	function stat_recent_update($RES)
	{
		$dt0 = new \DateTime();

		$out = [];
		$out[] = '<pre>';
		$out[] = $dt0->format(\DateTimeInterface::RFC3339);

		$dbc = _dbc();

		$sql = 'SELECT count(id) FROM %s WHERE updated_at >= :dt0';

		$dt0->sub(new \DateInterval('PT24H'));
		$arg = [ ':dt0' => $dt0->format(\DateTimeInterface::RFC3339) ];

		$out[] = sprintf('Variety: %04d', $dbc->fetchOne(sprintf($sql, 'variety'), $arg));
		$out[] = sprintf('Section: %04d', $dbc->fetchOne(sprintf($sql, 'section'), $arg));
		$out[] = sprintf('Product: %04d', $dbc->fetchOne(sprintf($sql, 'product'), $arg));
		$out[] = sprintf('Inventory: %04d', $dbc->fetchOne(sprintf($sql, 'lot'), $arg));
		$out[] = sprintf('Crop: %04d', $dbc->fetchOne(sprintf($sql, 'crop'), $arg));
		$out[] = sprintf('B2B_Sale: %04d', $dbc->fetchOne(sprintf($sql, 'b2b_sale'), $arg));
		$out[] = sprintf('B2B_Sale_Item: %04d', $dbc->fetchOne(sprintf($sql, 'b2b_sale_item'), $arg));
		$out[] = sprintf('B2C_Sale: %04d', $dbc->fetchOne(sprintf($sql, 'b2c_sale'), $arg));
		$out[] = sprintf('B2C_Sale_Item: %04d', $dbc->fetchOne(sprintf($sql, 'b2c_sale_item'), $arg));
		$out[] = '</pre>';

		return $RES->write(implode("\n", $out));

	}

}
