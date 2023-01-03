#!/usr/bin/php
<?php
/**
 * Use Curl to upload to the CCRS site
 *
 * SPDX-License-Identifier: MIT
 *
 * Get the cookies from var/
 * Upload the files one at a time, was some transactional lock like issues with bulk
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$dbc = _dbc();

$dtz = new \DateTimeZone( \OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));

$cookie_file = sprintf('%s/var/ccrs-cookies.json', APP_ROOT);
if ( ! is_file($cookie_file)) {
	echo "Cannot find Cookie File\n";
	exit(1);
}
$cookie_list0 = json_decode(file_get_contents($cookie_file), true);
foreach ($cookie_list0 as $c) {
	if (preg_match('/lcb\.wa\.gov/', $c['domain'])) {
		$cookie_list1[] = sprintf('%s=%s', $c['name'], $c['value']);
	}
}
sort($cookie_list1);

$src_file_list = _read_file_list($argv);

foreach ($src_file_list as $src_file) {

	sleep(1); // to give their system a little bit of time to rest

	$csrf_code = _get_main_page($cookie_list1);

	$mark = '----WebKitFormBoundaryAAAA8cKhBUv35ObB';
	$post = [];

	$src_data = file_get_contents($src_file);

	// Fix Name on Upload
	$src_name = basename($src_file);
	if (preg_match('/(\w+_\w+)_01\w{24}/', $src_name, $m)) {
		$dt0 = new \DateTime('now', $dtz);
		$src_name = sprintf('%s_%s.csv', $m[1], $dt0->format('Ymd\TGisv'));
	}

	$post[] = sprintf('--%s', $mark);
	$post[] = sprintf('content-disposition: form-data; name="files"; filename="%s"', $src_name);
	// $post[] = 'content-transfer-encoding: binary';
	$post[] = 'content-type: text/csv';
	$post[] = '';
	$post[] = $src_data;

	// Username
	$post[] = sprintf('--%s', $mark);
	$post[] = 'content-disposition: form-data; name="username"';
	$post[] = '';
	$post[] = \OpenTHC\Config::get('cre/usa/wa/ccrs/username');

	// RVT
	$post[] = sprintf('--%s', $mark);
	$post[] = 'content-disposition: form-data; name="__RequestVerificationToken"';
	$post[] = '';
	$post[] = $csrf_code; // Where to get this text?

	// Closer and Combine
	$post[] = sprintf('--%s--', $mark);
	$post = implode("\r\n", $post);
	// echo "Request Length: ";
	// echo strlen($post);
	// echo "\n";

	$upload_html = _post_home_upload($cookie_list1, $mark, $post);

	// Parse Upload Timestamp
	$upload_time = null;
	if (preg_match('/(Your submission was received at .+ Pacific Time)/', $upload_html, $m)) {

		$upload_time = $m[1];
		echo "Uploaded At: {$m[1]}\n";

		// Move Files
		$new_file = sprintf('%s/var/ccrs-complete/%s', APP_ROOT, basename($src_file));
		rename($src_file, $new_file);

		// Now Save in log_audit (log_upload)
		$src_data = file_get_contents($new_file);
		$req_code = null;
		$req_ulid = null;

		if (preg_match('/(\w+ UPLOAD (01\w+)).+-canary-/', $src_data, $m)) {

			$req_code = $m[1];
			$req_ulid = $m[2];

			$chk = $dbc->fetchRow('SELECT id FROM log_upload WHERE id = :u0', [ ':u0' => $req_ulid ]);
			if (empty($chk)) {
				$rec = [];
				$rec['id'] = $req_ulid;
				$rec['license_id'] = '';
				$rec['name'] = $req_code;
				$rec['source_data'] = json_encode([
					'name' => $src_name,
					'data' => $src_data
				]);
				$rec['result_data'] = json_encode([
					'@upload' => $upload_html,
				]);
				$dbc->insert('log_upload', $rec);
			} else {

				$sql = <<<SQL
				UPDATE log_upload
				SET updated_at = now(), result_data = coalesce(result_data, '{}'::jsonb) || :rd1::jsonb
				WHERE id = :u0
				SQL;

				$arg = [
					':u0' => $req_ulid,
					':rd1' => json_encode([
						'@upload' => $upload_html,
					])
				];
				// var_dump($arg);
				$dbc->query($sql, $arg);
				// var_dump($req_ulid);
				// exit;
			}

		} else {
			echo "NO MATCH, eval canary line, RE_SUMBIT?\n";
			echo "file: $new_file\n";
		}

	} else {
		echo $upload_html;
		throw new \Exception('No Match on Upload HTML');
	}

}

exit(0);

/**
 *
 */
function _get_main_page($cookie_list1) : string
{
	$base_url = \OpenTHC\Config::get('cre/usa/wa/ccrs/server');

	// Get to Verify Access and get RVT
	$req = __curl_init($base_url);
	// curl_setopt($req, CURLOPT_VERBOSE, true);
	curl_setopt($req, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36');
	curl_setopt($req, CURLOPT_HTTPHEADER, [
		'accept: text/html',
		sprintf('authority: %s', parse_url($base_url, PHP_URL_HOST)),
		sprintf('cookie: %s', implode('; ', $cookie_list1)),
		sprintf('origin: %s', $base_url),
		sprintf('referer: %s', $base_url),
	]);
	$res = curl_exec($req);
	$inf = curl_getinfo($req);
	if ('200' != $inf['http_code']) {
		echo "!! Not Authenticated\n";
		exit(1);
	}

	// I hate parsing HTML w/regex
	$csrf_code = preg_match('/<input name="__RequestVerificationToken" type="hidden" value="([^"]+)" \/>/', $res, $m) ? $m[1] : null;
	if (empty($csrf_code)) {
		echo "!! Cannot find RVT\n";
		exit(1);
	}

	return $csrf_code;

}

/**
 * Do the Upload
 */
function _post_home_upload($cookie_list1, $mark, $post) : string
{
	$base_url = \OpenTHC\Config::get('cre/usa/wa/ccrs/server');
	$base_url = rtrim($base_url, '/');
	$req = __curl_init(sprintf('%s/Home/Upload', $base_url));
	// curl_setopt($req, CURLOPT_VERBOSE, true);
	curl_setopt($req, CURLOPT_STDERR, fopen('php://stderr', 'a'));
	curl_setopt($req, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36');
	curl_setopt($req, CURLOPT_POST, true);
	curl_setopt($req, CURLOPT_POSTFIELDS, $post);
	curl_setopt($req, CURLOPT_HTTPHEADER, [
		'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
		'accept-language: en-US,en;q=0.9',
		sprintf('authority: %s', parse_url($base_url, PHP_URL_HOST)),
		'cache-control: max-age=0',
		sprintf('content-length: %d', strlen($post)),
		sprintf('content-type: multipart/form-data; boundary=%s', $mark),
		sprintf('cookie: %s', implode('; ', $cookie_list1)),
		sprintf('origin: %s', $base_url),
		sprintf('referer: %s', $base_url),
		// 'sec-ch-ua-mobile: ?0',
		// 'sec-ch-ua-platform: "Linux"',
		// 'sec-ch-ua: " Not;A Brand";v="99", "Google Chrome";v="97", "Chromium";v="97"',
		// 'sec-fetch-dest: document',
		// 'sec-fetch-mode: navigate',
		// 'sec-fetch-site: same-origin',
		// 'sec-fetch-user: ?1',
		// 'upgrade-insecure-requests: 1',
	]);

	$res = curl_exec($req);
	$inf = curl_getinfo($req);

	if (200 != $inf['http_code']) {
		echo "FAILED TO UPLOAD\n";
		exit(1);
	}

	// echo "Response Length: ";
	// echo strlen($res);
	// echo "\n";
	// echo "$res";

	return $res;

}

/**
 *
 */
function _read_file_list($source_list) : array
{
	$return_list = [];

	array_shift($source_list);

	if (empty($source_list)) {
		echo "No File List Provided [CCP-017]\n";
		exit(1);
	}

	foreach ($source_list as $file) {
		if (is_file($file)) {
			$return_list[] = $file;
		} else {
			echo "File: '$file' not found\n";
		}
	}

	return $return_list;

}
