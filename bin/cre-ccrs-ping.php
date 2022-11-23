#!/usr/bin/php
<?php
/**
 * Checks All CCRS Licenses for Access
 *
 * SPDX-License-Identifier: MIT
 */

require_once(dirname(dirname(__FILE__)) . '/boot.php');

$dbc = _dbc();

$cre_software_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

// All Sorta-Active Licenses
$sql = <<<SQL
SELECT *
FROM license
WHERE stat in (100, 200, 403)
ORDER BY id
SQL;

$res_license = $dbc->fetchAll($sql);
foreach ($res_license as $License) {

	// Generate dummy file to CCRS

	$req_ulid = _ulid();

	$csv_data = [];

	// Canary
	$csv_data[] = [
		'226279',
		sprintf('SECTION UPLOAD %s', $req_ulid),
		'FALSE',
		'-canary-',
		'-canary-',
		date('m/d/Y'),
		'',
		'',
		'INSERT',
	];

	// Fake Record
	$row = [
		$License['code']
		, 'Main Section'
		, 'FALSE'
		, sprintf('%s-%s', $License['code'], '018NY6XC00SECT10N000000000')
		, '-system-'
		, date('m/d/Y')
		, ''
		, ''
		, 'INSERT'
	];


	// INSERT into BONG DATA TOO
	$sql = <<<SQL
	INSERT INTO section (id, license_id, name, data, hash)
	VALUES (:pk, :l0, :n0, :d0, :h0)
	ON CONFLICT (id) DO
	UPDATE SET updated_at = now(), stat = 100, hash = :h0, data = section.data || :d0
	WHERE section.id = :pk AND section.license_id = :l0 AND section.hash != :h0
	SQL;

	$arg = [
		':pk' => $row[3],
		':l0' => $License['id'],
		':h0' => sha1(json_encode($row)),
		':n0' => $row[1],
		':d0' => json_encode([
			'@result' => [],
			'@source' => $row,
		])
	];

	$dbc->query($sql, $arg);

	// Ping Data
	$csv_data[] = $row;

	// INSERT FILE
	$csv_file = sprintf('area_%s_%s.csv', $cre_software_key, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$output_row_count = count($csv_data);
	$output_csv = fopen('php://temp', 'w');
	$output_col = count($csv_head);
	fputcsv($output_csv, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $output_col, '')));
	fputcsv($output_csv, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $output_col, '')));
	fputcsv($output_csv, array_values(array_pad([ 'NumberRecords', $output_row_count ], $output_col, '')));
	fputcsv($output_csv, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($output_csv, $row);
	}
	fseek($output_csv, 0);

	// NOW POST TO SELF?
	$cfg = array(
		'base_uri' => 'https://bong.openthc.dev/',
		'allow_redirects' => false,
		'cookies' => false,
		'headers' => array(
			'user-agent' => sprintf('OpenTHC/%s', APP_BUILD),
			'openthc-company' => $License['company_id'],
			'openthc-contact' => '',
			'openthc-license' => $License['id'],
		),
		'http_errors' => false,
		'verify' => false,
	);
	$api_bong = new \GuzzleHttp\Client($cfg);

	$arg = [
		'headers' => [
			'content-name' => basename($csv_file),
			'content-type' => 'text/csv',
			'openthc-company' => $License['company_id'],
			'openthc-license' => $License['id'],
			'openthc-license-code' => $License['code'],
			'openthc-license-name' => $License['name'],
		],
		'body' => $output_csv // this resource is closed by Guzzle
	];
	// var_dump($arg);
	$res = $api_bong->post('/upload/outgoing', $arg);

	$hrc = $res->getStatusCode();
	$buf = $res->getBody()->getContents();
	$buf = trim($buf);

	echo "## BONG $csv_file = $hrc\n";

	unset($output_csv);

}
