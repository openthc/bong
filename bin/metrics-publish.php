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
	'b2b_incoming_item',
	'b2b_outgoing_item',
];

foreach ($tab_list as $tab_name) {

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

foreach ($metric_list as $key => $val) {
	echo "$key=$val\n";
	_stat_gauge($key, $val);
}
