#!/usr/bin/php
<?php
/**
 * Tools for CCRS
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

openlog('openthc-bong', LOG_ODELAY | LOG_PID, LOG_LOCAL0);

$doc = <<<DOC
BONG CRE CCRS Upload Tool
Usage:
	cre-ccrs <command> [<command-options>...]

Commands:
	auth                  Authenticate to CCRS
	push                  Does upload-single for all the stuff in the queue
	upload-create         from source data create the csv files in the upload queue
	upload-single         Uploads a Single Job
	upload-status         Show Status of The Upload Thoughts
	license-status        Show License Status
	license-verify        Re-Init a License and try to Verify via magic Section
	review                Review Data 400 Level Errors

Options:
	--license=<LIST>
	--object=<LIST>
DOC;

$res = Docopt::handle($doc, [
	'help' => true,
	'optionsFirst' => true,
]);
$cli_args = $res->args;

switch ($cli_args['<command>']) {
	case 'auth':
		_cre_ccrs_auth($cli_args['<command-options>']);
		break;
	case 'license-status':
		require_once(__DIR__ . '/cre-ccrs-license-status.php');
		_cre_ccrs_license_status($cli_args['<command-options>']);
		break;
	case 'license-verify':
		_cre_ccrs_license_verify($cli_args['<command-options>']);
		break;
	case 'push':
		_cre_ccrs_push($cli_args['<command-options>']);
		break;
	case 'upload-b2b-outgoing-file':
		require_once(__DIR__ . '/cre-ccrs-upload-b2b-outgoing-file.php');
		_cre_ccrs_upload_b2b_outgoing_file($cli_args['<command-options>']);
		break;
	case 'review':
		_cre_ccrs_review($cli_args['<command-options>']);
		// require_once(__DIR__ . '/cre-ccrs-review-inventory-variety.php');
		break;
	case 'upload-create':
		_cre_ccrs_csv_upload_create($cli_args['<command-options>']);
		break;
	case 'upload-single':
		_cre_ccrs_upload_single($cli_args['<command-options>']);
		break;
	case 'upload-status':
		_cre_ccrs_upload_status($cli_args['<command-options>']);
		break;
	default:
		var_dump($cli_args);
		exit(1);
}


/**
 *
 */
function _cre_ccrs_auth($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS Authentication
	Usage:
		auth [options]

	Options:
		--ping
		--refresh
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	// Get
	$cfg = \OpenTHC\Config::get('cre/usa/wa/ccrs');
	$cfg['cookie-list'] = _cre_ccrs_auth_cookies();
	// var_dump($cfg['cookie-list']);

	$cre = new \OpenTHC\CRE\CCRS($cfg);

	// // The Compliance Engine
	// $cfg = \OpenTHC\CRE::getConfig('usa/wa');
	// $cre = \OpenTHC\CRE::factory($cfg);
	// $tz0 = new DateTimezone($cfg['tz']);

	// Check & Refresh if Needed
	if (empty($cli_args['--ping']) && empty($cli_args['--refresh'])) {
		_stat_count('bong_cre_ccrs_auth_ping', 1);
		$res = $cre->ping();
		if (200 != $res['code']) {
			$cli_args['--refresh'] = true;
		}
	}

	if ( ! empty($cli_args['--ping'])) {

		$res = $cre->ping();
		echo $res['code'] . "\n";

		switch ($res['code']) {
			case 200:

				syslog(LOG_DEBUG, 'AUTH SUCCESS');

				// Good
				$out = [];
				$out[] = 'Cookies Alive';
				if ( ! empty($res['csrf'])) {
					$out[] = 'Found CSRF';
				}
				echo implode('; ', $out);
				echo "\n";

				exit(0);

			case 302:

				syslog(LOG_DEBUG, 'AUTH FAILURE [BCC-143]');

				break;

			default:
				throw new \Exception('Invalid Response from CRE [CCA-049]');
		}

	} elseif ( ! empty($cli_args['--refresh'])) {

		_stat_count('bong_cre_ccrs_auth_open', 1);

		// Needs Auth
		try {

			// $cookie_data = $cre->auth($cfg['username'], $cfg['password']);
			// $cookie_data = json_encode($cookie_data, JSON_PRETTY_PRINT);

			$cookie_data = shell_exec('docker container exec -t ed1a966198ed /opt/openthc/bong/lcb-sign-in.php 2>&1');
			if (empty($cookie_data)) {
				_stat_count('bong_cre_ccrs_auth_open_fail', 1);
			}

			// Save
			$rdb = \OpenTHC\Service\Redis::factory();
			$chk = $rdb->set('/cre/ccrs/auth-cookie-list', $cookie_data, 60 * 10);

			syslog(LOG_DEBUG, 'AUTH UPDATED');


		} catch (\Exception $e) {
			var_dump($e);
			syslog(LOG_DEBUG, 'AUTH FAILURE [BCC-158]');
		}

	}

}


/**
 * Get my Auth Cookies
 */
function _cre_ccrs_auth_cookies()
{
	$cookie_list = [];

	$rdb = \OpenTHC\Service\Redis::factory();
	$chk = $rdb->get('/cre/ccrs/auth-cookie-list');
	if ( ! empty($chk)) {
		$cookie_list = json_decode($chk, true);
	}

	return $cookie_list;

}


/**
 * Generate CSV Files for the Pending Objects
 */
function _cre_ccrs_csv_upload_create($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS Upload Script Creator

	Create a shell script to upload data for each license

	Usage:
		upload-create [--license=<LIST>] [--object=<LIST>] [--force]

	Options:
		--license=<LIST>      comma-list of license [default: ALL]
		--object=<LIST>       comma-list of objects [default: section,variety,product,crop,crop-collect,inventory,inventory-adjust,b2b-incoming,b2b-outgoing]
		--force
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	// Lock
	$key = implode('/', [ __FILE__, $cli_args['--license'] ]);
	$lock = new \OpenTHC\CLI\Lock($key);
	if ( ! $lock->create()) {
		syslog(LOG_DEBUG, sprintf('LOCK: "%s" Failed', $key));
		return 0;
	}

	$dbc = _dbc();

	$license_list = [];
	if ('ALL' == $cli_args['--license']) {
		$license_list = $dbc->fetchAll('SELECT id, code, name FROM license WHERE stat IN (200, 202)');
	} else {
		// @todo Allow for a LIST of License IDs
		$sql = 'SELECT id, code, name FROM license WHERE id = :l0';
		$arg = [ ':l0' => $cli_args['--license'] ];
		$license_list = $dbc->fetchAll($sql, $arg);
	}

	foreach ($license_list as $license0) {

		syslog(LOG_NOTICE, "cre-ccrs-upload-create {$license0['id']} {$license0['name']}");

		$cmd = [];
		$cmd[] = sprintf('%s/bin/cre-ccrs-upload.php', APP_ROOT);
		$cmd[] = sprintf('--license=%s', $license0['id']);
		$cmd[] = sprintf('--object=%s', $cli_args['--object']);
		if ( ! empty($cli_args['--force'])) {
			$cmd[] = '--force';
		}
		$cmd[] = '2>&1';
		$cmd = implode(' ', $cmd);
		// echo "$cmd\n";
		passthru($cmd);

	}

}


/**
 * Push Queue from log_upload to CCRS
 */
function _cre_ccrs_push($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS Push

	Pushes data from the Upload Queue into CCRS

	Usage:
		push [--license=<ID>] [--upload-id=<ID>]

	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;


	$dbc = _dbc();
	// $dbc->query('BEGIN');

	$arg = [];
	$sql = <<<SQL
	SELECT id, license_id, name, created_at
	FROM log_upload
	WHERE
	  stat = 100
	ORDER BY id ASC
	LIMIT 100
	SQL;

	if ( ! empty($cli_args['--license'])) {
		$sql = str_replace('stat = 100', 'stat = 100 AND license_id = :l0', $sql);
		$arg[':l0'] = $cli_args['--license'];
	}

	$res_upload = $dbc->fetchAll($sql, $arg);

	if (0 == count($res_upload)) {
		syslog(LOG_DEBUG, 'No Files to Upload');
		exit(0);
	}

	_cre_ccrs_auth([]);

	foreach ($res_upload as $rec) {

		$idx++;

		$opt = [ 'upload-single', "--upload-id={$rec['id']}" ];
		$res = _cre_ccrs_upload_single($opt);
		switch ($res['code']) {
		case 200:
		case 204:
			// OK
			break;
		default:
			var_dump($res);
			return $res['code'];
		}

		// go slow to not make their IDS trip up
		sleep(1);
		// usleep(1.5 * 1000 * 1000);

		$dt0 = new \DateTime($rec['created_at']);
		$dt1 = new \DateTime();
		$ddX = $dt0->diff($dt1);
		$tms = ($ddX->days * 86400) + ($ddX->h * 3600) + ($ddX->i * 60) + $ddX->s + $ddX->f;

		_stat_timer('openthc_bong_ccrs_upload_push_lag', $tms);
	}

}

/**
 * Review for 400 Errors
 */
function _cre_ccrs_review($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS Data Review
	Usage:
		review [--license=<LICENSE>] [--object=<LIST>]
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	require_once(__DIR__ . '/cre-ccrs-review.php');

}


/**
 * Upload a Single Item from the Log_Upload Records
 */
function _cre_ccrs_upload_single($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS Upload
	Usage:
		cre-ccrs upload-single --upload-id=ULID
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	$req_ulid = $cli_args['--upload-id'];
	if (empty($req_ulid)) {
		echo "Invalid --upload-id\n";
		exit(1);
	}

	syslog(LOG_DEBUG, "cre-ccrs-upload-single {$req_ulid}");

	$dbc = _dbc();
	$rdb = \OpenTHC\Service\Redis::factory();

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

	if ( ! empty($src['data']) && ! empty($src['name'])) {

		// Special Case our Manifests
		if (preg_match('/^Manifest.+/', $src['name'])) {

			$b2b_outgoing_id = '';

			// Lookup the Manifest ID?
			if (preg_match('/ExternalManifestIdentifier,(.+),,,,,,,,,,/', $src['data'], $m)) {

				$b2b_outgoing_id = $m[1];
				$chk0 = $dbc->fetchRow('SELECT id, stat FROM b2b_outgoing WHERE id = :b0', [
					':b0' => $b2b_outgoing_id
				]);
				$chk1 = $dbc->fetchRow('SELECT id, name FROM b2b_outgoing_file WHERE id = :b0', [
					':b0' => $b2b_outgoing_id
				]);

				if ( ! empty($chk0['id']) && ! empty($chk1['id'])) {

					echo "SEEMS ALREADY UPLOADED\n";
					$dbc->query('UPDATE log_upload SET stat = 208 WHERE id = :l0', [
						':l0' => $req_ulid
					]);

					return [
						'code' => 204,
						'data' => '',
						'meta' => [],
					];

				}

			} else {
				throw new \Exception('Cannot find Manifest Identifier [BCC-388]');
				exit(1);
			}

			// Plate Number
			// Shouldn't have to do this after 2025-11-25
			// if (preg_match('/VehiclePlateNumber,(.+),,,,,,,,,,/', $src['data'], $m)) {
			// 	$tag0 = $m[1];
			// 	$tag1 = str_replace(' ', '', $tag0);
			// 	$tag1 = substr($tag1, 0, 7);
			// 	if ($tag0 != $tag1) {
			// 		echo "Fixing Vehicle Tag\n";
			// 		$src['data'] = preg_replace('/VehiclePlateNumber,(.+),,,,,,,,,,/',
			// 			sprintf('VehiclePlateNumber,%s,,,,,,,,,,', $tag1),
			// 			$src['data']);
			// 	}
			// }

			// $src['data'] = preg_replace(
			// 	'/VehicleColor,,,,,,,,,,,/',
			// 	'VehicleColor,COLOR,,,,,,,,,,',
			// 	$src['data']);

			// $src['data'] = preg_replace(
			// 	'/VehicleModel,,,,,,,,,,,/',
			// 	'VehicleModel,MODEL,,,,,,,,,,',
			// 	$src['data']);

		}

		// Upload
		$cfg = \OpenTHC\CRE::getConfig('usa-wa');
		// $cfg = \OpenTHC\Config::get('cre/usa/wa/ccrs');
		$cfg['cookie-list'] = _cre_ccrs_auth_cookies();
		$cre = new \OpenTHC\CRE\CCRS($cfg);

		_stat_count('bong_cre_ccrs_upload', 1);

		$res = $cre->upload($src);
		switch ($res['code']) {
			case 200:

				_stat_count('bong_cre_ccrs_upload_200', 1);

				$log_stat = 102;

				switch ($req['stat']) {
					case 100:
						$log_stat = 102;
						break;
					case 102:
						// Second Upload Attempt?
						// Only happens if there is a cache-status timeout
						$log_stat = 104;
						break;
				}

				// Save in Database
				$sql = <<<SQL
				UPDATE log_upload
				SET stat = :s1, updated_at = now(), result_data = coalesce(result_data, '{}'::jsonb) || :rd1::jsonb
				WHERE id = :u0
				SQL;

				$arg = [
					':u0' => $req_ulid,
					':s1' => $log_stat,
					':rd1' => json_encode([
						'@upload' => $res,
					])
				];

				$dbc->query($sql, $arg);

				syslog(LOG_NOTICE, "Uploaded: {$res['meta']['created_at']}");

				$license_id = $req['license_id'];
				$upload_type = preg_match('/(B2B_INCOMING|B2B_OUTGOING|CROP|CROP_COLLECT|INVENTORY|INVENTORY_ADJUST|PRODUCT|SECTION|VARIETY) UPLOAD/', $req['name'], $m) ? $m[1] : null;
				if (empty($upload_type)) {
					if (preg_match('/ExternalManifestIdentifier,[^,]+,,,,,,,,,,/', $src['data'], $m)) {
						// $upload_type = 'manifest';
						$upload_type = 'b2b_outgoing_notice';
					}
				}
				if (empty($upload_type)) {
					throw new \Exception('Unknown Upload Type');
				}
				$upload_type = strtolower($upload_type);
				$upload_type = str_replace('_', '/', $upload_type);

				$status = new \OpenTHC\Bong\CRE\CCRS\Status($license_id, $upload_type);
				$status->setPush(200);

				break;

			case 302:

				// typically, authentication has timed out
				// print_r($res);
				_stat_count('bong_cre_ccrs_upload_403', 1);
				syslog(LOG_DEBUG, 'AUTH TIMEOUT [BCC-590]');
				return [
					'code' => 403,
					'data' => '',
					'meta' => [ 'note' => 'AUTH TIMEOUT [BCC-590]' ],
				];

			default:
				syslog(LOG_DEBUG, 'FAILED TO UPLOAD [CUS-581]');
				var_dump($res);
				throw new \Exception('FAILED TO UPLOAD [CUS-581]');
		}

	}

	$rdb->hset('/cre/ccrs', 'push/time', date(\DateTimeInterface::RFC3339));

	return [
		'code' => 200,
		'data' => '',
		'meta' => [],
	];
}

/**
 * Upload a Single Item from the Log_Upload Records
 */
function _cre_ccrs_upload_status($cli_args) {

	$doc = <<<DOC
	BONG CRE CCRS Upload Status
	Usage:
		upload-status [--license=<LIST>] [--object=<LIST>]

	Options:
		--license=<LIST>      comma-list of license
		--object=<LIST>       comma-list of objects [default: section,variety,product,crop,inventory,inventory-adjust,b2b-incoming,b2b-outgoing]
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	$dbc = _dbc();
	$rdb = \OpenTHC\Service\Redis::factory();

	$license_list = [];
	if (empty($cli_args['--license'])) {
		$license_list = $dbc->fetchAll('SELECT id, code, name FROM license WHERE stat IN (200, 202)');
	} else {
		// @todo Allow for a LIST of License IDs
		$sql = 'SELECT id, code, name FROM license WHERE id = :l0';
		$arg = [ ':l0' => $cli_args['--license'] ];
		$license_list = $dbc->fetchAll($sql, $arg);
	}

	foreach ($license_list as $license0) {

		echo "License: {$license0['id']} {$license0['code']} {$license0['name']}\n";

		$stat = $rdb->hgetall(sprintf('/license/%s', $license0['id']));
		ksort($stat);
		// var_dump($stat);
		$key_list = [
			'variety',
			'section',
			'product',
			'inventory',
			'crop',
			'b2b/incoming',
			'b2b/outgoing',
		];
		foreach ($key_list as $key) {

		}
		// foreach ($stat as $s )
		// var_dump($stat);
		// exit;


	}

}


/**
 *
 */
function _cre_ccrs_license_verify($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS Verification
	Usage:
		license-verify --license=LICENSE [--force] [--reset]

	Options:
		--force    will force the verify, even if stat is locked
		--reset    will reset all data to stat 100 to start over
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	$dbc = _dbc();
	$License = new \OpenTHC\Bong\License($dbc, $cli_args['--license']);

	// $V = new \OpenTHC\Bong\CRE\CCRS\License\Verify($dbc, $License);
	// $V->verify();
	// $sc = new \Slim\Container();
	// $C = new OpenTHC\Bong\Controller\License\Verify($sc);
	// $C->__invoke(null, null, [ 'id' => $License['id'] ]);

	$req_ulid = _ulid();
	$req_code = "SECTION UPLOAD $req_ulid";

	$csv_data = [];
	$csv_data[] = [ '-canary-', $req_code, 'FALSE', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
	$csv_data[] = [
		$License['code']
		, 'OPENTHC SECTION PING'
		, 'FALSE'
		, 'OPENTHC SECTION PING'
		, '-system-'
		, date('m/d/Y')
		, '-system-'
		, date('m/d/Y')
		, 'DELETE'
	];

	$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
	$csv_name = sprintf('Area_%s_%s.csv', $cre_service_key, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);
	$row_size = count($csv_data);
	$csv_temp = fopen('php://temp', 'w');

	// Output
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}
	fseek($csv_temp, 0);

	// Add to Database
	$rec = [];
	$rec['id'] = $req_ulid;
	$rec['license_id'] = $License['id'];
	$rec['name'] = $req_code;
	$rec['source_data'] = json_encode([
		'name' => $csv_name,
		'data' => stream_get_contents($csv_temp)
	]);

	$dbc->insert('log_upload', $rec);

	// Hard-Reset?
	if ($cli_args['--reset']) {
		$License->resetData();
	}

}
