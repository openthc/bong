#!/usr/bin/php
<?php
/**
 * Tools for CCRS
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

define('COOKIE_FILE', sprintf('%s/var/ccrs-cookies.json', APP_ROOT));
define('COOKIE_FILE_NEXT', sprintf('%s/var/ccrs-cookies-%s.json', APP_ROOT, _ulid()));


$doc = <<<DOC
BONG CRE CCRS Upload Tool
Usage:
	cre-ccrs --license <command> [<command-options>...]
	cre-ccrs [options] <command> [<command-options>...]

Options:
	--none
	--license=LICENSE

Commands:
	auth
	single
	upload
	verify
DOC;

$res = Docopt::handle($doc, [
	'help' => true,
	'optionsFirst' => true,
]);
$cli_args = $res->args;
var_dump($cli_args);

switch ($cli_args['<command>']) {
	case 'auth':
		_cre_ccrs_auth(array_merge([ 'auth' ], $cli_args['<command-options>']));
		break;
	case 'upload':
		_cre_ccrs_upload(array_merge([ 'upload' ], $cli_args['<command-options>']));
		break;
	case 'upload-queue':
		// require_once(APP_ROOT . '/lib/CRE/ccrs/cli/upload-queue.php')
		// _cre_ccrs_upload_queue(array_merge([ 'upload-queue' ], $cli_args['<command-options>']));
		$R = \OpenTHC\Service\Redis::factory();
		$key_list = $R->keys('/license/*/variety');
		foreach ($key_list as $k) {
			if (preg_match('/license\/(\w+)\/variety/', $k, $m)) {
				$l = $m[1];
				echo "./bin/cre-ccrs-upload.php upload --license=$l --object=variety\n";
				$R->del($k);
			}
		}
		exit;
		// $R->set('/license/%s/stat', 100);  ', $req_ulid);

		while ($k = $R->lpop('/cre/ccrs/upload-queue')) {
			echo "QUEUE: $k\n";
			echo "./bin/cre-ccrs.php upload-single --upload-id={$k}\n";
			exit(0);
		}

		$key_list = $R->keys('/license/*');
		foreach ($key_list as $k) {

			$license_id = null;
			$dataset = null;

			if (preg_match('/\/license\/(\w+)\/([\w\-]+)/', $k, $m)) {
				$license_id = $m[1];
				$dataset = $m[2];
			}

			$val = $R->get($k);
			switch ($val) {
				case 100:
					// Trigger Upload for this License
					$cmd = [];
					// $cmd[] = sprintf('%s/bin/cre-ccrs.php', APP_ROOT);
					// $cmd[] = 'upload-object';

					$cmd[] = sprintf('%s/bin/cre-ccrs-upload.php', APP_ROOT);
					$cmd[] = 'upload';
					$cmd[] = sprintf('--license=%s', $license_id);
					$cmd[] = sprintf('--object=%s', $dataset);
					$cmd[] = '2>&1';
					$cmd = implode(' ', $cmd);
					echo "$cmd\n";

					// ./bin/cre-ccrs-upload.php upload --license=01CAV122Q843RESRTRFK96RTT5 --object=variety

					break;
			}
		}

		break;

	case 'status':
		_cre_ccrs_status(array_merge([ 'status' ], $cli_args['<command-options>']));
		break;
	case 'upload-create':
		_cre_ccrs_upload_create(array_merge([ 'upload-create' ], $cli_args['<command-options>']));
		break;
	case 'upload-single':
		_cre_ccrs_upload_single(array_merge([ 'upload-single' ], $cli_args['<command-options>']));
		break;
	case 'verify':
		_cre_ccrs_upload_verify(array_merge([ 'verify' ], $cli_args['<command-options>']));
		break;
}

/**
 *
 */
function _cre_ccrs_auth($args)
{
	// var_dump($args);

	$doc = <<<DOC
	BONG CRE CCRS Authentication
	Usage:
		cre-ccrs auth [options]

	Options:
		--ping
		--refresh
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $args,
		// 'optionsFirst' => true,
	]);
	$cli_args = $res->args;

	// Get
	$cfg = \OpenTHC\Config::get('cre/usa/wa/ccrs');
	$cfg['cookie-list'] = _cre_ccrs_auth_cookies();

	$cre = new \OpenTHC\CRE\CCRS($cfg);

	// Check & Refresh if Needed
	if (empty($cli_args['--ping']) && empty($cli_args['--refresh'])) {
		$res = $cre->ping();
		if (200 != $res['code']) {
			$cli_args['--refresh'];
		}
	}

	if ( ! empty($cli_args['--ping'])) {

		$res = $cre->ping();

		switch ($res['code']) {
			case 200:

				echo "AUTH SUCCESS\n";

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

				echo "AUTH FAILURE\n";

				break;

			default:
				throw new \Exception('Invalid Response from CRE [CCA-049]');
		}

	} elseif ( ! empty($cli_args['--refresh'])) {

		// Needs Auth
		$cookie_data = $cre->auth($cfg['username'], $cfg['password']);

		// Save
		$d = json_encode($cookie_data, JSON_PRETTY_PRINT);
		$x = file_put_contents(COOKIE_FILE_NEXT, $d);
		if (false === $x) {
			throw new \Exception('Error writing to: COOKIE_FILE_NEXT');
		}

		if ( ! rename(COOKIE_FILE_NEXT, COOKIE_FILE)) {
			throw new \Exception('Error writing to: COOKIE_FILE');
		}

		echo "AUTH UPDATED\n";

	}

}


/**
 * Get my Auth Cookies
 */
function _cre_ccrs_auth_cookies()
{
	$cookie_list = [];
	$cookie_life_max = 60 * 4;

	// DELETE if older than 4 minutes
	if (is_file(COOKIE_FILE)) {
		$t0 = time();
		$t1 = filemtime(COOKIE_FILE);
		$tX = $t0 - $t1;
		if ($tX > $cookie_life_max) {
			unlink(COOKIE_FILE);
		}
	}

	if (is_file(COOKIE_FILE)) {
		$cookie_list = json_decode(file_get_contents(COOKIE_FILE), true);
	}

	return $cookie_list;

}


function _cre_ccrs_upload($args)
{
	// var_dump($args);

	$doc = <<<DOC
	BONG CRE CCRS Authentication
	Usage:
		cre-ccrs upload <OBJECT> [OID]

	Options:
		OBJECT is one of:
			variety|section|product|crop|inventory|b2b\-incoming|b2b\-outgoing|b2b\-outgoing\-manifest

		OID is the Objects ID
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $args,
		// 'optionsFirst' => true,
	]);
	$cli_args = $res->args;
	var_dump($cli_args);


}

/**
 *
 */
function _cre_ccrs_upload_verify($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS Verification
	Usage:
		cre-ccrs verify --license=LICENSE
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	$dbc = _dbc();

	$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [
		':l0' => $cli_args['--license'],
	]);
	$License['guid'] = $License['code'];

	$cfg = [
		'server' => 'https://bong.openthc.com/',
		'company' => $License['company_id'],
		'contact' => '',
		'license' => $License['id'],
	];
	// var_dump($cfg);

	$jwt = new \OpenTHC\JWT([
		'iss' => 'bong.openthc.com',
		'exp' => (time() + 120),
		'sub' => '',
		'company' => $cfg['company'],
		'license' => $cfg['license'],
		'service' => 'bong', // CRE or BONG or PIPE?
		'cre' => 'usa/wa/ccrs', // CRE ID
	]);

	$cre = new \OpenTHC\CRE\OpenTHC($cfg);
	// $cre->setLicense($License);

	$url = sprintf('/license/%s/verify', $License['id']);
	$res = $cre->request('POST', $url, [
		'headers' => [
			'openthc-jwt' => $jwt->__toString(),
			'openthc-company' => $cfg['company'],
			'openthc-license' => $cfg['license'],
		]
	]);
	var_dump($res);

	echo $res->getStatusCode();
	echo $res->getBody()->getContents();

}
