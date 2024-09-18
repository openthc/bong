#!/usr/bin/php
<?php
/**
 * Wrapper for the Upload Scripts
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

openlog('openthc-bong', LOG_ODELAY | LOG_PID, LOG_LOCAL0);

$doc = <<<DOC
BONG CRE CCRS Upload Tool
Usage:
	cre-ccrs-upload --license=LICENSE_ID [--object=<OBJECT>] [--object-id=<OBJECT_ID>] [--force]

Options:
	--license=ID        The license ID of the one to work on.
	--object=TYPE...    The type of record to work on. [default: section,variety,product,crop,inventory,inventory-adjust,b2b-incoming,b2b-outgoing]
	--object-id=ID      To UPLOAD only a single item.
	--force
DOC;

$res = Docopt::handle($doc);
$cli_args = $res->args;

// Lock
$sync_lock_txt = implode('/', [ __FILE__, $cli_args['--license'] ]);
$sync_lock_key = crc32($sync_lock_txt);
$sync_lock_sem = sem_get($sync_lock_key, 1, 0666, true);
$sync_lock_ack = sem_acquire($sync_lock_sem, true);
if (empty($sync_lock_ack)) {
	echo "LOCK: $sync_lock_txt\n";
	exit(0);
}


$dbc = _dbc();
$License = _load_license($dbc, $cli_args['--license']);

// Action
$obj_list = explode(',', $cli_args['--object']);

// Check Parameters
foreach ($obj_list as $obj) {
	if ( ! preg_match('/^(section|variety|product|crop|crop\-finish|inventory|inventory\-adjust|b2b\-incoming|b2b\-outgoing|b2b\-outgoing\-manifest)$/', $obj)) {
		echo "Cannot Match Object [CCU-058]\n";
		exit(1);
	}
}

// Run the Scripts
foreach ($obj_list as $obj) {

	switch ($obj) {
		case 'product':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Product\CSV($License);
			$csv->create($cli_args['--force']);
			break;
		case 'section':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Section\CSV($License);
			$csv->create($cli_args['--force']);
			break;
		case 'variety':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Variety\CSV($License);
			// $csv->setForce($cli_args['--force']);
			$csv->create($cli_args['--force']);
			break;
		default:

			$obj_file = sprintf('%s/cre-ccrs-upload-%s.php', __DIR__, $obj);
			require_once($obj_file);

			$obj = str_replace('-', '_', $obj);
			$obj_func = sprintf('_cre_ccrs_upload_%s', $obj);

			// Improve Args?
			$res = call_user_func($obj_func, $cli_args);
	}
}


/**
 * Utility Functions
 */
function _load_license($dbc, $license_id, $object_table=null)
{
	$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $license_id ]);
	if (empty($License['id'])) {
		echo "Invalid License '{$license_id}' [CCU-071]\n";
		exit(1);
	}
	switch ($License['stat']) {
		case 100:
		case 102:
		case 200:
			// OK
			break;
		case 403:
		case 500:
		case 666:
			// $dbc->query("UPDATE {$object_table} SET stat = :s1 WHERE license_id = :l0 AND stat != :s1", [
			// 	':l0' => $license_id,
			// 	':s1' => $License['stat']
			// ]);
			// Pass Thru
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
	$url_base = \OpenTHC\Config::get('openthc/bong/origin');

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
			'openthc-company' => $License['company_id'], // v0
			'openthc-company-id' => $License['company_id'], // v1
			'openthc-license' => $License['id'], // v0
			'openthc-license-id' => $License['id'], // v1
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
	echo ">> $buf ..\n";

}
