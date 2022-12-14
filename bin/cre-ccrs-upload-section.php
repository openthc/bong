#!/usr/bin/php
<?php
/**
 * Create Upload for Section Data
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$tz0 = new DateTimezone(\OpenTHC\Config::get('cre/usa/wa/ccrs/tz'));
$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');

$dbc = _dbc();

$license_id = array_shift($argv);
$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $license_id ]);
if (empty($License['id'])) {
	echo "Invalid License\n";
	exit(1);
}

_section_upload($License);

// $sql = 'SELECT * FROM license WHERE stat IN (100, 200) ORDER BY id';
// $res_license = $dbc->fetchAll($sql);
// foreach ($res_license as $License) {
// 	_section_upload($License);
// }

function _section_upload($License)
{
	global $dbc;
	global $tz0, $cre_service_key;

	$req_ulid = _ulid();
	$csv_data = [];
	$csv_data[] = [ '-canary-', "SECTION UPLOAD $req_ulid", 'FALSE', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
	// DELETE Legacy Value
	$csv_data[] = [
		$License['code']
		, 'Main Section'
		, 'FALSE'
		, sprintf('%s-%s', $License['code'], '018NY6XC00SECT10N000000000')
		, '-system-'
		, date('m/d/Y')
		, '-system-'
		, date('m/d/Y')
		, 'DELETE'
	];
	$csv_name = sprintf('product_%s_%s.csv', $cre_service_key, $req_ulid);
	$csv_head = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
	$col_size = count($csv_head);
	$csv_temp = fopen('php://temp', 'w');

	// Section 100s
	$sql = <<<SQL
	SELECT section.*, license.code AS license_code
	FROM section
	JOIN license ON section.license_id = license.id
	WHERE section.stat = 100
	AND license.id = :l0
	SQL;
	$res_section = $dbc->fetchAll($sql, [ ':l0' => $License['id'] ]);
	foreach ($res_section as $section) {
		// INSERT
		$dtC = new \DateTime($section['created_at'], $tz0);
		$dtU = new \DateTime($section['updated_at'], $tz0);

		$csv_data[] = [
			$section['license_code']
			, $section['name']
			, 'FALSE'
			, $section['id']
			, '-system-'
			, $dtC->format('m/d/Y')
			, '-system-'
			, $dtU->format('m/d/Y')
			, 'INSERT'
		];

	}

	$output_row_count = count($csv_data);
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $output_row_count ], $col_size, '')));
	\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
	foreach ($csv_data as $row) {
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
	}

	// $res_section = $dbc->fetchAll('SELECT * FROM section WHERE license_id = :l0', [ ':l0' => $License['id'] ]);
	// $res_section = $dbc->fetchAll('SELECT * FROM section WHERE license_id = :l0', [ ':l0' => $License['id'] ]);

	// Upload
	fseek($csv_temp, 0);

	_upload_to_queue_only($License, $csv_name, $csv_temp);

	unset($csv_temp);

}
