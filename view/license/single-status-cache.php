<?php
/**
 * Object Status Cache Table
 *
 * SPDX-License-Identifier: MIT
 */

if ( ! empty($data['redis-cache'])) {
	$key_list = [
		'variety',
		'section',
		'product',
		'inventory',
		'inventory/adjust',
		'crop',
		'crop/finish',
		'b2b/incoming',
		'b2b/outgoing',
	];
	echo '<table class="table table-sm">';
	echo '<thead class="table-dark">';
	echo '<tr><th>Object</th><th>Stat</th><th>Push</th><th>Pull</th></tr>';
	echo '</thead>';
	echo '<tbody>';
	foreach ($key_list as $k0) {

		echo '<tr>';
		printf('<td>%s</td>', $k0);

		$val = $data['redis-cache'][sprintf('%s/stat', $k0)];
		$val = _apply_object_sync_status_style($val);
		$dt0 = $data['redis-cache'][sprintf('%s/stat/time', $k0)];
		printf('<td>%s [%s]</td>', $val, _nice_date($dt0));

		$val = $data['redis-cache'][sprintf('%s/push', $k0)];
		$val = _apply_object_sync_status_style($val);
		$dt0 = $data['redis-cache'][sprintf('%s/push/time', $k0)];
		printf('<td>%s [%s]</td>', $val, _nice_date($dt0));

		$val = $data['redis-cache'][sprintf('%s/pull', $k0)];
		$val = _apply_object_sync_status_style($val);
		$dt0 = $data['redis-cache'][sprintf('%s/pull/time', $k0)];
		printf('<td>%s [%s]</td>', $val, _nice_date($dt0));

		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
	// foreach ($data['redis-cache'] as $k => $v1) {
	// }
}
