#!/usr/bin/php
<?php
/**
 * Generate Metrics
 *
 * SPDX-License-Identifier: MIT
 */

require_once(__DIR__ . '/../boot.php');

$dbc = _dbc();

$metric_list = [];

$stat_list = [ 100, 102, 200, 202, 400 ];

$tab_list = [
	'section',
	'variety',
	'product',
	'crop',
	'inventory',
	'b2b_incoming',
	'b2b_incoming_item',
	'b2b_outgoing',
	'b2b_outgoing_item',
];

foreach ($tab_list as $tab_name) {

	// Initialize to Zero
	foreach ($stat_list as $s) {
		$key = sprintf('openthc_bong_%s_%s', $tab_name, $s);
		$metric_list[$key] = 0;
	}

	$sql = <<<SQL
	SELECT count(id) AS c, stat
	FROM %s
	GROUP BY stat
	ORDER BY stat
	SQL;

	$sql = sprintf($sql, $tab_name);
	$res = $dbc->fetchAll($sql, $arg);

	foreach ($res as $rec) {
		$key = sprintf('openthc_bong_%s_%s', $tab_name, $rec['stat']);
		$val = $rec['c'];
		$metric_list[$key] = $val;
	}

}

// Publish Metrics
foreach ($metric_list as $key => $val) {
	// echo "$key=$val\n";
	_stat_gauge($key, $val);
}


// Select Count
$sql = <<<SQL
SELECT count(id)
FROM log_upload
WHERE created_at >= now() - '96 hours'::interval
  AND stat = 102
SQL;

$res = $dbc->fetchOne($sql);
$res = intval($res);

$rdb = \OpenTHC\Service\Redis::factory();
$rdb->set('/cre/ccrs/upload/wait', $res);
_stat_gauge('openthc_bong_cre_ccrs_upload_wait', $res);
