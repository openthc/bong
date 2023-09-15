<?php
/**
 * Browse Things Controller
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller;

class Browse extends \OpenTHC\Controller\Base
{
	// List of HTTP args & tables
	protected $table_list = [
		'b2b/incoming' => 'b2b_incoming',
		'b2b/outgoing' => 'b2b_outgoing',
		'b2b/rejected' => 'b2b_outgoing',
		'b2c' => 'b2c',
		// 'batch' => 'batch',
		'company' => 'company',
		'contact' => 'contact',
		'crop' => 'crop',
		'crop/collect' => 'crop_collect',
		'lab' => 'lab_result',
		'license-type' => 'license_type',
		'license' => 'license',
		'lot' => 'lot',
		// 'lot/disposal' => '',
			// 	$sql = 'SELECT * FROM b2b_incoming ORDER BY id OFFSET 0 LIMIT 100';
			// 	$data['object_list'] = $dbc->fetchAll($sql);
			// 	break;
		'lot/history' => 'lot_delta',
		'product-type' => 'product_type',
		'product' => 'product',
		'section' => 'section',
		'variety' => 'variety',
		'vehicle' => 'vehicle',
	];

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		session_write_close();

		if (empty($_SESSION['sql-conn'])) {
			_exit_html('<h1>Invalid Session</h1><p>you must <a href="/auth/open">sign in</a> again.</p>', 403);
		}

		if ( ! empty($_GET['a'])) {

			if ( ! empty($_GET['id'])) {
				return $this->object_single($REQ, $RES);
			}

			return $this->browse_object_search($REQ, $RES);
		}

		$data = [];
		$data['Page'] = [ 'title' => 'Browse' ];
		$data['cre_auth'] = $_SESSION['cre-auth'];
		$data['cre_meta_license'] = $_SESSION['cre-auth']['license'];
		$data['cre_meta_license'] = $_SESSION['License']['id'];

		$dbc = $REQ->getAttribute('dbc');
		$data['cre_sync'] = $dbc->fetchAll("SELECT * FROM base_option WHERE key LIKE 'sync-%' ORDER BY key");

		switch ($_POST['a']) {
			case 'sync':
				$html = $this->execute_sync();
				return $RES->write($html);
			break;
		}

		$html = $this->render('browse.php', $data);

		return $RES->write($html);

	}

	/**
	 * @deprecated?
	 */
	function browse_object_search($REQ, $RES)
	{
		$dbc = $REQ->getAttribute('dbc');

		$data = [];
		$data['Page'] = [ 'title' => 'Browse' ];
		$data['cre_auth'] = $_SESSION['cre-auth'];
		$data['cre_meta_license'] = $_SESSION['cre-auth']['license'];

		switch ($_GET['a']) {
			case 'b2b/incoming':
				$sql = 'SELECT * FROM b2b_incoming ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'b2b/outgoing':
				$sql = 'SELECT * FROM b2b_outgoing ORDER BY created_at OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'b2b/rejected':
				$sql = 'SELECT * FROM b2b_outgoing ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'b2c':
				$sql = 'SELECT * FROM b2c ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'batch':
				$sql = 'SELECT * FROM batch ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'company':
				$sql = 'SELECT * FROM company ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'contact':
				$sql = 'SELECT * FROM contact ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'crop':
				$sql = 'SELECT * FROM crop ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'crop/collect':
				$sql = 'SELECT * FROM crop_collect ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'lab':
				$sql = 'SELECT * FROM lab_result ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'license-type':
				$sql = 'SELECT * FROM license_type ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'license':
				$sql = 'SELECT * FROM license ORDER BY id OFFSET 0';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'lot':
				$sql = 'SELECT * FROM lot ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			// case 'lot/disposal':
			// 	$sql = 'SELECT * FROM b2b_incoming ORDER BY id OFFSET 0 LIMIT 100';
			// 	$data['object_list'] = $dbc->fetchAll($sql);
			// 	break;
			case 'lot/history':
				$sql = 'SELECT * FROM lot_delta ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'product-type':
				$sql = 'SELECT * FROM product_type ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'section':
				$sql = 'SELECT * FROM section ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'variety':
				$sql = 'SELECT * FROM variety ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
			case 'vehicle':
				$sql = 'SELECT * FROM vehicle ORDER BY id OFFSET 0 LIMIT 100';
				$data['object_list'] = $dbc->fetchAll($sql);
				break;
				// OK
			default:
				_exit_text_fail('<h1>Invalid Object Request [BCB-074]', 400);
		}

		return $RES->write( $this->render('browse/search.php', $data) );

	}

	/**
	 * View Single Object
	 */
	function object_single($REQ, $RES)
	{
		$tab = $this->table_list[ $_GET['a'] ];
		if (empty($tab)) {
			throw new \Exception('Invalid Table');
		}

		$dbc = $REQ->getAttribute('dbc');

		$data = [];
		$data['Page'] = [ 'title' => 'Browse :: Single Object' ];
		// $data['cre_auth'] = $_SESSION['cre-auth'];
		// $data['cre_meta_license'] = $_SESSION['cre-auth']['license'];

		$sql = sprintf('SELECT * FROM %s WHERE id = :o1', $tab);
		$arg = [ ':o1' => $_GET['id'] ];
		$obj0 = $dbc->fetchRow($sql, $arg);

		$data['obj0'] = $obj0;
		$data['obj0_type'] = $tab;

		switch ($tab) {
			case 'license':

				$obj0_subject_list = [
					'section',
					'product',
					'crop',
					'lot',
					// 'b2b_incoming'
					// 'b2b_outgoing'
					// 'b2c',
				];

				foreach ($obj0_subject_list as $sub1) {

					$sql = sprintf('SELECT id, name, hash, flag, stat, created_at, updated_at FROM %s WHERE license_id = :l0 ORDER BY id', $sub1);
					$arg = [ ':l0' => $obj0['id'] ];

					$res = $dbc->fetchAll($sql, $arg);
					$key = sprintf('obj0_subject_%s', $sub1);

					$data[$key] = $res;

				}

				$data['obj0_subject_list'] = $obj0_subject_list;

				break;
		}


		return $RES->write( $this->render('browse/single.php', $data) );
	}

	/**
	 * Execute the Sync Script in the Background
	 */
	function execute_sync()
	{
		$data = $_SESSION;

		unset($data['_radix']);
		unset($data['crypt-key']);

		$out_html = [];

		// Prepare
		$data = json_encode($data, JSON_PRETTY_PRINT);
		$hash = sha1($data);
		$out_html[] = "<p>Generated Hash: $hash</p>";

		// Stash
		$file = sprintf('%s/var/sync-%s.json', APP_ROOT, $hash);
		file_put_contents($file, $data);
		$out_html[] = "<p>Stashed File: $file</p>";

		// Build Command
		$cmd = [];
		$cmd[] = sprintf('%s/bin/sync.php --config=%s', APP_ROOT, $file);
		$cmd[] = sprintf('>>%s/var/sync-%s.log', APP_ROOT, $hash);
		$cmd[] = '2>&1';
		$cmd[] = '&';
		$cmd[] = 'echo $!';
		$cmd = implode(' ', $cmd);
		$out_html[] = "<p>Command:</p><pre>$cmd</pre>";

		// Launch
		$buf = shell_exec($cmd);
		$buf = trim($buf);
		$out_html[] = "<p>Output <small>(pid)</small>:</p><pre>$buf</pre>";

		// Alert
		$out_html[] = '<p>Sync Started. Back to <a href="/browse">/browse</a>.</p>';

		return implode("\n", $out_html);

	}

}
