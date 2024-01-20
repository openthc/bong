<?php
/**
 *
 */

$rdb = \OpenTHC\Service\Redis::factory();

$key_list_full = [];

$need_only = $_GET['need'] == 'only';

?>
<table class="table table-sm table-hover">
<thead class="table-dark"><tr><th>License</th><th>Stat</th><th>Name</th><th>Variety</th><th>Section</th><th>Product</th><th>Crop</th><th>Inventory</th></tr></thead>
<tbody>
<?php
$row_output = [];
foreach ($data['license_list'] as $rec) {

	ob_start();

	echo '<tr>';
	printf('<td><a href="/license/%s">%s</a></td>', $rec['id'], $rec['code']);
	printf('<td>%d</td>', __h($rec['stat']));
	printf('<td>%s</td>', __h($rec['name']));

	// $val = $rdb->get
	$val = $rdb->hgetall(sprintf('/license/%s', $rec['id']));
	ksort($val);

	$key_list_temp = array_keys($val);
	foreach ($key_list_temp as $k) {
		$key_list_full[$k]++;
	}

	$key_list = [];
	$key_list[] = 'variety/stat';
	$key_list[] = 'section/stat';
	$key_list[] = 'product/stat';
	$key_list[] = 'crop/stat';
	$key_list[] = 'inventory/stat';
	// $key_list[] = 'variety/push';

	$sum_stat = 0;
	foreach ($key_list as $k) {
		switch ($val[$k]) {
			case 202:
				printf('<td title="%s %s">%d</td>', $k, $val[ sprintf('%s/time', $k) ], $val[$k]);
				break;
			default:
				printf('<td class="fw-bold text-danger" title="%s %s">%d</td>', $k, $val[ sprintf('%s/time', $k) ], $val[$k]);
				break;
		}

		$sum_stat += ($val[$k]);
	}

	//
	//

	// printf('<td>%s</td>', json_encode($val, JSON_UNESCAPED_SLASHES));

	echo '</tr>';

	$row = ob_get_clean();

	$row_output[] = [
		'html' => $row,
		'sort' => $sum_stat,
	];

}

uasort($row_output, function($a, $b) {
	$ret = ($a['sort'] > $b['sort']);
	return $ret;
});

foreach ($row_output as $row) {
	echo $row['html'];
}

?>
</tbody>
</table>

<pre>
<?php
// ksort($key_list_full);
// var_dump($key_list_full);
?>
</pre>
