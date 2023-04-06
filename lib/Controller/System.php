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
		switch ($_GET['a']) {
			case 'object-upload-result':
				$this->ajax_object_result();
				break;
			case 'object-upload-status':
				$this->ajax_object_stat();
				break;
		}

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

	/**
	 *
	 */
	function ajax_object_stat() : array
	{
		$dbc = _dbc();

		$RET = [];

		$sql = 'select count(id) AS c, stat from license GROUP BY stat ORDER BY stat';
		$RET['license'] = $dbc->fetchAll($sql);

		echo '<h3>License</h3>';
		echo '<table class="table table-sm">';
		foreach ($RET['license'] as $idx => $rec) {

			printf('<tr><td>%d</td><td>%d</td></tr>'
				, $rec['stat']
				, $rec['c']
			);
		}
		echo '</table>';

		$sql = 'select count(id) AS c, stat from section GROUP BY stat ORDER BY stat';
		$RET['section'] = $dbc->fetchAll($sql);


		$sql = 'select count(id) AS c, stat from product GROUP BY stat ORDER BY stat';
		$RET['product'] = $dbc->fetchAll($sql);

		$sql = 'select count(id) AS c, stat from variety GROUP BY stat ORDER BY stat';
		$RET['variety'] = $dbc->fetchAll($sql);

		$sql = 'select count(id) AS c, stat from crop GROUP BY stat ORDER BY stat';
		$RET['crop'] = $dbc->fetchAll($sql);

		$sql = 'select count(id) AS c, stat from inventory GROUP BY stat ORDER BY stat';
		$RET['inventory'] = $dbc->fetchAll($sql);

		$sql = 'select count(id) AS c, stat from b2b_sale_item GROUP BY stat ORDER BY stat';
		$RET['b2b_sale_item'] = $dbc->fetchAll($sql);

		// echo "B2C\n";
		// $sql = 'select count(id), stat from b2b GROUP BY stat ORDER BY stat';
		// $res = $dbc->fetchAll($sql);
		// print_r($res);

		return $RET;
	}

	/**
	 *
	 */
	function ajax_object_result()
	{
		$dbc = _dbc();

		// $res = $dbc->fetchAll("select count(id) AS c, stat AS e FROM license GROUP BY 2 ORDER BY 1 DESC");
		// echo '<h3>License</h3>';
		// echo '<table class="table table-sm">';
		// foreach ($res as $rec) {
		// 	printf("% 6d : %s\n", $rec['c'], $rec['e']);
		// }

		$sql = "SELECT count(id) AS c, stat, data->'@result'->'data' AS e FROM %s GROUP BY 2, 3 ORDER BY 1, 2 DESC";

		$res = $dbc->fetchAll(sprintf($sql, 'section'));
		$out = $this->ajax_object_result_output('section', $res);
		echo '<h3>Section</h3>';
		echo $this->html_table_wrap(implode('', $out));

		$res = $dbc->fetchAll(sprintf($sql, 'variety'));
		$out = $this->ajax_object_result_output('variety', $res);
		echo '<h3>Variety</h3>';
		echo $this->html_table_wrap(implode('', $out));

		$res = $dbc->fetchAll(sprintf($sql, 'product'));
		$out = $this->ajax_object_result_output('product', $res);
		echo '<h3>Product</h3>';
		echo $this->html_table_wrap(implode('', $out));

		$res = $dbc->fetchAll(sprintf($sql, 'crop'));
		$out = $this->ajax_object_result_output('crop', $res);
		echo '<h3>Crop</h3>';
		echo $this->html_table_wrap(implode('', $out));

		$res = $dbc->fetchAll(sprintf($sql, 'inventory'));
		$out = $this->ajax_object_result_output('inventory', $res);
		echo '<h3>Lot</h3>';
		echo $this->html_table_wrap(implode('', $out));

		$res = $dbc->fetchAll(sprintf($sql, 'b2b_sale_item'));
		$out = $this->ajax_object_result_output('b2b_sale_item', $res);
		echo '<h3>B2B Item</h3>';
		echo $this->html_table_wrap(implode('', $out));

		// $res = $dbc->fetchAll(sprintf($sql, '')"select count(id), data->'result'->'data' from b2b_incoming_item);

		// $res = $dbc->fetchAll("select count(id), data->'result'->'data' from b2c_item);

		exit(0);

	}

	/**
	 * Output Helper
	 */
	function ajax_object_result_output($obj, $res)
	{
		$ret = [];
		foreach ($res as $rec) {
			$ret[] = sprintf('<tr><td>%d</td><td><a href="/%s?q=%s">%s</a></td><td>%d</td></tr>'
				, $rec['stat']
				, $obj
				, rawurlencode($rec['e'])
				, $rec['e']
				, $rec['c']
			);
		}
		return $ret;
	}

	function html_table_wrap($html)
	{
		return sprintf('<table class="table table-sm">%s</table>', $html);
	}

	function license_type($REQ, $RES, $ARG)
	{
		// var_dump($_SESSION);
		return _from_cre_file('license-type.php', $REQ, $RES, $ARG);
	}

	function product_type($REQ, $RES, $ARG)
	{
		// var_dump($_SESSION);
		return _from_cre_file('product-type.php', $REQ, $RES, $ARG);
	}

}
