<?php
/**
 *
 */

$rdb = \OpenTHC\Service\Redis::factory();

// Object Keys
$obj_key_list = [];
$obj_key_list[] = 'variety/stat';
$obj_key_list[] = 'section/stat';
$obj_key_list[] = 'product/stat';
$obj_key_list[] = 'crop/stat';
$obj_key_list[] = 'inventory/stat';
$obj_key_list[] = 'b2b/incoming';
$obj_key_list[] = 'b2b/outgoing';
// $obj_key_list[] = 'variety/push';

// Action Keys
$act_key_list = [ 'stat', 'push', 'pull' ];

?>

<div class="container-fluid">
<h1>License :: Status :: Full</h1>


<table class="table table-sm table-hover">
<thead class="table-dark">
	<tr>
		<th>License</th>
		<th>Stat</th>
		<th>Name</th>
		<th>Variety</th>
		<th>Section</th>
		<th>Product</th>
		<th>Crop</th>
		<th>Inventory</th>
		<th>B2B/I</th>
		<th>B2B/O</th>
	</tr>
</thead>
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

	$sum_stat = 0;
	foreach ($obj_key_list as $k) {
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

	echo '</tr>';

	$row = ob_get_clean();

	$row_output[] = [
		'html' => $row,
		'sort' => $sum_stat,
	];

}

// uasort($row_output, function($a, $b) {
// 	$ret = ($a['sort'] > $b['sort']);
// 	return $ret;
// });

foreach ($row_output as $row) {
	echo $row['html'];
}

?>
</tbody>
</table>

</div>
