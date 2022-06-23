#!/usr/bin/php
<?php
/**
 * Use Curl to upload to the CCRS site
 * Get the cookies from var/
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$dbc = _dbc();

$file_list = _read_file_list($argv);

$cookie_file0 = sprintf('%s/var/ccrs-cookies.json', APP_ROOT);
$cookie_list0 = json_decode(file_get_contents($cookie_file0), true);
foreach ($cookie_list0 as $c) {
	if ('cannabisreporting.lcb.wa.gov' == $c['domain']) {
		$cookie_list1[] = sprintf('%s=%s', $c['name'], $c['value']);
	}
}
sort($cookie_list1);

// Get to Verify Access and get RVT
$req = __curl_init('https://cannabisreporting.lcb.wa.gov/');
// curl_setopt($req, CURLOPT_VERBOSE, true);
curl_setopt($req, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36');
curl_setopt($req, CURLOPT_HTTPHEADER, [
	'accept: text/html',
	'authority: cannabisreporting.lcb.wa.gov',
	sprintf('cookie: %s', implode('; ', $cookie_list1)),
	'origin: https://cannabisreporting.lcb.wa.gov',
	'referer: https://cannabisreporting.lcb.wa.gov/',
]);
$res = curl_exec($req);
$inf = curl_getinfo($req);
if ('200' != $inf['http_code']) {
	echo "Not Authenticated\n";
	exit(1);
}

// I hate parsing HTML w/regex
$rvt_code = preg_match('/<input name="__RequestVerificationToken" type="hidden" value="([^"]+)" \/>/', $res, $m) ? $m[1] : null;
if (empty($rvt_code)) {
	echo "Cannot find RVT\n";
	exit(1);
}

$req_file_list = [];

$part_mark = '----WebKitFormBoundaryAAAA8cKhBUv35ObB';
$post = [];

foreach ($file_list as $f) {

	$src_data = file_get_contents($f);

	$post[] = sprintf('--%s', $part_mark);
	$post[] = sprintf('content-disposition: form-data; name="files"; filename="%s"', basename($f));
	// $post[] = 'content-transfer-encoding: binary';
	$post[] = 'content-type: text/csv';
	$post[] = '';
	$post[] = $src_data;

}

// Username
$post[] = sprintf('--%s', $part_mark);
$post[] = 'content-disposition: form-data; name="username"';
$post[] = '';
$post[] = 'code@openthc.com';

// RVT
$post[] = sprintf('--%s', $part_mark);
$post[] = 'content-disposition: form-data; name="__RequestVerificationToken"';
$post[] = '';
$post[] = $rvt_code; // Where to get this text?

// Closer and Combine
$post[] = sprintf('--%s--', $part_mark);
$post = implode("\r\n", $post);
echo "Request Length: ";
echo strlen($post);
echo "\n";

$req = __curl_init('https://cannabisreporting.lcb.wa.gov/Home/Upload');
// curl_setopt($req, CURLOPT_VERBOSE, true);
curl_setopt($req, CURLOPT_STDERR, fopen('php://stderr', 'a'));
curl_setopt($req, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36');
curl_setopt($req, CURLOPT_POST, true);
curl_setopt($req, CURLOPT_POSTFIELDS, $post);
curl_setopt($req, CURLOPT_HTTPHEADER, [
	'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
	'accept-language: en-US,en;q=0.9',
	'authority: cannabisreporting.lcb.wa.gov',
	'cache-control: max-age=0',
	sprintf('content-length: %d', strlen($post)),
	sprintf('content-type: multipart/form-data; boundary=%s', $part_mark),
	sprintf('cookie: %s', implode('; ', $cookie_list1)),
	'origin: https://cannabisreporting.lcb.wa.gov',
	'referer: https://cannabisreporting.lcb.wa.gov/',
	'sec-ch-ua-mobile: ?0',
	'sec-ch-ua-platform: "Linux"',
	'sec-ch-ua: " Not;A Brand";v="99", "Google Chrome";v="97", "Chromium";v="97"',
	'sec-fetch-dest: document',
	'sec-fetch-mode: navigate',
	'sec-fetch-site: same-origin',
	'sec-fetch-user: ?1',
	'upgrade-insecure-requests: 1',
]);
$res = curl_exec($req);
$inf = curl_getinfo($req);
// print_r($inf);

echo "Response Length: ";
echo strlen($res);
echo "\n";
// echo "$res";

// Parse Upload Timestamp
$res_time = null;
if (preg_match('/(Your submission was received at .+ Pacific Time)/', $res, $m)) {

	$res_time = $m[1];
	echo "Uploaded At: {$m[1]}\n";

	// Remove Files
	foreach ($file_list as $old_file) {

		$new_file = sprintf('%s/var/ccrs-complete/%s', APP_ROOT, basename($old_file));
		rename($old_file, $new_file);

		$src_data = file_get_contents($new_file);
		$req_code = null;
		$req_ulid = null;
		if (preg_match('/(\w+ UPLOAD (01\w+)).+-canary-/', $src_data, $m)) {

			$req_code = $m[1];
			$req_ulid = $m[2];

			$rec = [];
			$rec['id'] = $req_ulid;
			$rec['name'] = $req_code;
			$rec['source_data'] = $src_data;
			$dbc->insert('log_upload', $rec);

		} else {
			echo "NO MATCH, Eval Canary LIne, RE_SUMBIT?\n";
			echo "file: $new_file\n";
		}

	}

} else {
	echo "No Match on Upload Stuff\n";
}

exit(0);


function _read_file_list($source_list)
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
