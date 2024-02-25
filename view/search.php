<?php
/**
 * View a Table of the requested objects
 *
 * SPDX-License-Identifier: MIT
 */

echo '<div class="container">';

printf('<h1>%s</h1>', $data['Page']['title']);

foreach ($data['search_result'] as $tab => $res) {
	_draw_result_table($tab, $res);
}

echo '</div>';


/**
 *
 */
function _draw_result_table($tab, $res)
{
	switch ($tab) {
		case 'b2b_incoming':
			$tab = 'b2b/incoming';
			break;
		case 'b2b_outgoing':
			$tab = 'b2b/outgoing';
			break;
	}

	echo '<section class="mt-2">';
	printf('<h2>Table: %s</h2>', __h($tab));

	if (empty($res)) {
		echo '<div class="alert alert-warning">No results found</div>';
		echo '</section>';
		return;
	}


	$chk = $res[0];
	// unset($chk['id']);
	//if (empty($data['column_list'])) {
		$column_list = array_keys($chk);
	//}

	echo '<table class="table table-sm table-bordered table-hover">';
	echo '<thead class="table-dark">';
	echo '<tr>';
	foreach ($column_list as $k) {
		printf('<th>%s</th>', $k);
	}
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	foreach ($res as $obj) {

		echo '<tr>';

		// printf('<td><a href="/browse?%s">%s<?a></td>'
		// 	, http_build_query(array_merge($_GET, [ 'id' => $obj['id'] ]))
		// 	, $obj['id']
		// );

		foreach ($column_list as $k) {
			switch ($k) {
				case 'id':
					printf('<td><a href="/%s/%s">%s</a></td>', $tab, $obj[$k], $obj[$k]);
					break;
				default:
				//if (isset($data['column_function'][$k])) {
				//	echo "\n<!-- $k -->\n";
				//	echo $data['column_function'][$k]($obj[$k], $obj);
				//} else {
					printf('<td>%s</td>', __h($obj[$k]));
				//}
			}
		}

		echo '</tr>';
		echo "\n";

	}
	echo '</tbody>';
	echo '</table>';

	echo '</section>';
}
