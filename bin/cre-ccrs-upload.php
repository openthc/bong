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
if (! preg_match('/^(create|variety|section|product|crop|inventory|b2b\-incoming|b2b\-outgoing)$/', $action)) {
	echo "Cannot Match Action\n";
	exit(1);
}

/**
 * Create the Upload Script-Set for a License
 */
if ('create' == $action) {

	$dbc = _dbc();

	// CCRS v2021-340
	echo "./bin/cre-ccrs-upload.php variety\n";

	$sql = 'SELECT * FROM license WHERE stat IN (100, 200)';
	$arg = [];
	$res_license = $dbc->fetchAll($sql, $arg);
	foreach ($res_license as $l0) {

		echo "# License: {$l0['id']} / {$l0['name']}\n";

		// CCRS v2022-343
		// echo "./bin/cre-ccrs-upload.php variety {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php section {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php product {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php crop {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php inventory {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php inventory-delta {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php b2b-incoming {$l0['id']}\n";
		echo "./bin/cre-ccrs-upload.php b2b-outgoing {$l0['id']}\n";

	}

	exit(0);
}

$action_file = sprintf('%s/cre-ccrs-upload-%s.php', __DIR__, $action);
if (is_file($action_file)) {
	include_once($action_file);
}

exit(0);

/**
 * Utility Functions
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
	// var_dump($arg);
	$res = $api_bong->post('/upload/outgoing', $arg);

	$hrc = $res->getStatusCode();
	$buf = $res->getBody()->getContents();
	$buf = trim($buf);

	echo "## BONG $csv_name = $hrc\n";
	echo $buf;


}
