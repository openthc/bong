#!/usr/bin/php
<?php
/**
 * Wrapper for the Upload Scripts
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$script = array_shift($argv);
$action = array_shift($argv);

$action_file = null;
if (! preg_match('/^(create|single|variety|section|product|crop|inventory|b2b\-incoming|b2b\-outgoing|b2b\-outgoing\-manifest)$/', $action)) {
	echo "Cannot Match Action [CCU-018]\n";
	exit(1);
}

/**
 * Create the Upload Script-Set for a License
 */
if ('create' == $action) {

	$dbc = _dbc();
	$license_id = array_shift($argv);

	$sql = 'SELECT * FROM license WHERE stat IN (100, 200)';
	$arg = [];

	if ($license_id) {
		$sql = 'SELECT * FROM license WHERE id = :l0';
		$arg[':l0'] = $license_id;
	}

	$res_license = $dbc->fetchAll($sql, $arg);
	foreach ($res_license as $l0) {

		echo "# License: {$l0['id']} / {$l0['name']}\n";

		echo "./bin/cre-ccrs-upload.php variety {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php section {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php product {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php crop {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php inventory {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php inventory-delta {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php b2b-incoming {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php b2b-outgoing {$l0['id']}\n";

	}

} elseif ('single' == $action) {
	$req_ulid = array_shift($argv);
	_upload_single($req_ulid);
} else {

	$action_file = sprintf('%s/cre-ccrs-upload-%s.php', __DIR__, $action);
	if (is_file($action_file)) {
		include_once($action_file);
	}

}

/**
 * Utility Functions
 */
function _load_license($dbc, $license_id)
{
	$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $license_id ]);
	if (empty($License['id'])) {
		echo "Invalid License '{$license_id}' [CCU-071]\n";
		exit(1);
	}
	switch ($License['stat']) {
		case 100:
		case 200:
			// OK
			break;
		default:
			echo "Invalid License:'$license_id' status:'{$License['stat']}'\n";
			exit(1);
	}

	return $License;

}

/**
 *
 */
function _upload_to_queue_only(array $License, string $csv_name, $csv_data)
{
	$url_base = \OpenTHC\Config::get('openthc/bong/base');

	$cfg = array(
		'base_uri' => $url_base,
		'allow_redirects' => false,
		'cookies' => false,
		'http_errors' => false,
		'verify' => false,
	);
	$api_bong = new \GuzzleHttp\Client($cfg);

	$arg = [
		'headers' => [
			'content-name' => basename($csv_name),
			'content-type' => 'text/csv',
			'openthc-company' => $License['company_id'],
			'openthc-license' => $License['id'],
			'openthc-license-code' => $License['code'],
			'openthc-license-name' => $License['name'],
			'openthc-disable-update' => true,
		],
		'body' => $csv_data // this resource is closed by Guzzle
	];

	// if (getenv('OPENTHC_BONG_DUMP)'))
	if ( ! empty($_SERVER['argv'])) {
		$argv = implode(' ', $_SERVER['argv']);
		if (strpos($argv, '--dump')) {
			if (is_resource($arg['body'])) {
				$arg['body'] = stream_get_contents($arg['body']);
			}
			var_dump($arg['headers']);
			echo ">>>\n{$arg['body']}###\n";
			return;
		}
	}

	$res = $api_bong->post('/upload/outgoing', $arg);

	$hrc = $res->getStatusCode();
	$buf = $res->getBody()->getContents();
	$buf = trim($buf);

	echo "## BONG $csv_name = $hrc\n";
	echo $buf;

}

/**
 * Upload a Single Item from the Log_Upload Records
 */
function _upload_single($req_ulid)
{
	$cfg = \OpenTHC\Config::get('cre/usa/wa/ccrs');

	$dbc = _dbc();

	$req = $dbc->fetchRow('SELECT * FROM log_upload WHERE id = :r0', [ ':r0' => $req_ulid ]);
	if (empty($req['id'])) {
		echo "Failed\n";
		exit(1);
	}

	if (empty($req['source_data'])) {
		echo "Failed [CCU-067]";
		exit(1);
	}

	$src = json_decode($req['source_data'], true);

	if (preg_match('/Manifest__(\w+)\.csv$/', $src['name'], $m)) {
		$src['name'] = sprintf('Manifest_%s_%s.csv', $cfg['service-key'], $m[1]);
	}

	//print_r($src);
	if ( ! empty($src['data']) && ! empty($src['name'])) {


		$cookie_file = sprintf('%s/var/ccrs-cookies.json', APP_ROOT);
		if ( ! is_file($cookie_file)) {
			echo "Cannot find Cookie File\n";
			exit(1);
		}
		$cookie_list = json_decode(file_get_contents($cookie_file), true);
		// foreach ($cookie_list0 as $c) {
		// 	if (preg_match('/lcb\.wa\.gov/', $c['domain'])) {
		// 		$cookie_list1[] = sprintf('%s=%s', $c['name'], $c['value']);
		// 	}
		// }

		$cfg['cookie-list'] = $cookie_list;
		// var_dump($cfg);

		$cre = new \OpenTHC\CRE\CCRS($cfg);
		$res = $cre->ping();
		if (200 != $res['code']) {
			$res = $cre->auth($cfg['username'], $cfg['password']);
			var_dump($res);
		}

		$res = $cre->upload($src);
		switch ($res['code']) {
			case 200:

				// OK

				// Save in Database
				$sql = <<<SQL
				UPDATE log_upload
				SET updated_at = now(), result_data = coalesce(result_data, '{}'::jsonb) || :rd1::jsonb
				WHERE id = :u0
				SQL;

				$arg = [
					':u0' => $req_ulid,
					':rd1' => json_encode([
						'@upload' => $res,
					])
				];

				$dbc->query($sql, $arg);

				echo "Uploaded: {$res['meta']['created_at']}\n";

				break;
			default:
				var_dump($res);
				exit(1);
		}

	}
}
