<?php
/**
 * View a Table of the requested objects
 *
 * SPDX-License-Identifier: MIT
 */

$chk = $data['object_list'][0];
// unset($chk['id']);
if (empty($data['column_list'])) {
	$data['column_list'] = array_keys($chk);
}


echo '<table class="table table-sm table-hover">';
echo '<thead class="table-dark">';
echo '<tr>';
foreach ($data['column_list'] as $k) {
	printf('<th>%s</th>', $k);
}
echo '</tr>';
echo '</thead>';
echo '<tbody>';
foreach ($data['object_list'] as $obj) {

	echo '<tr>';

	// printf('<td><a href="/browse?%s">%s<?a></td>'
	// 	, http_build_query(array_merge($_GET, [ 'id' => $obj['id'] ]))
	// 	, $obj['id']
	// );

	foreach ($data['column_list'] as $k) {
		if (isset($data['column_function'][$k])) {
			echo $data['column_function'][$k]($obj[$k], $obj);
		} else {
			printf('<td>%s</td>', __h($obj[$k]));
		}

	}

	echo '</tr>';

}
echo '</tbody>';
echo '</table>';
