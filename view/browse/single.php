<?php
/**
 * View Single Object Details
 *
 * SPDX-License-Identifier: MIT
 */

?>

<style>
h1, h2 {
	margin: 0;
}
</style>

<?php

echo '<div class="container-fluid">';

printf('<h1><code>%s</code> :: %s</h1>'
	, __h($data['obj0_type'])
	, __h($data['obj0']['name'])
);

if ('license' == $data['obj0_type']) {
	echo '<div>';
	printf('<a class="btn btn-outline-secondary me-2" href="https://ops.openthc.com/company/view?id=%s">OPS</a>', $data['obj0']['company_id']);
	printf('<a class="btn btn-outline-secondary me-2" href="https://directory.openthc.com/company/%s">DIR-Company</a>', $data['obj0']['company_id']);
	printf('<a class="btn btn-outline-secondary me-2" href="https://directory.openthc.com/license/%s">DIR-License</a>', $data['obj0']['id']);
	echo '</div>';
}

echo '<pre>';
print_r($data['obj0']);
echo '</pre>';

foreach ($data['obj0_subject_list'] as $sub1) {

	$key = sprintf('obj0_subject_%s', $sub1);
	$sub_data = $data[$key];

	echo '<hr>';
	printf('<h2 style="margin: 0;">%s [%d]</h2>', $sub1, count($sub_data));

	echo '<table class="table table-sm table-striped table-hoverqq">';
	echo '<thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Stat</th><th>Flag</th><th>Created</th><th>Updated</th></tr></thead>';
	foreach ($sub_data as $rec) {

		$dtC = new DateTime($rec['created_at']); // [];
		$dtU = new DateTime($rec['updated_at']); // [];

		echo '<tr>';
		printf('<td><a href="/browse?a=%s&amp;id=%s">%s</a></td>', $sub1, $rec['id'], $rec['id']);
		printf('<td>%s</td>', __h($rec['name']));
		printf('<td>%s</td>', __h($rec['stat']));
		printf('<td>%s</td>', __h($rec['flag']));
		printf('<td>%s</td>', $dtC->format('Y-m-d H:i'));
		printf('<td>%s</td>', $dtU->format('Y-m-d H:i'));
		echo '</tr>';

	}
	echo '</table>';

}

?>

</div>
