<?php
/**
 *
 */

$tz = new \DateTimezone($data['tz']);

$l = $this->query_limit;
$offset = intval($_GET['o']);

$link_back = http_build_query(array_merge($_GET, [ 'o' => max(0, $offset - $l) ] ));
$link_next = http_build_query(array_merge($_GET, [ 'o' => $offset + $l ] ));

?>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="initial-scale=1, user-scalable=yes">
<meta name="application-name" content="OpenTHC BONG">
<link rel="stylesheet" href="/css/app.css" crossorigin="anonymous">
<title>Log Search :: OpenTHC BONG</title>
</head>
<body>

<form autocomplete="off">
<div class="search-filter">
	<div>
		<input list="subject-list" name="subject" placeholder="Type to search...">
		<datalist id="subject-list">
			<option>- any -</option>
			<option>b2b_sale</option>
			<option>b2c_sale</option>
			<option>disposal</option>
			<option>crop</option>
			<option>lab_result</option>
			<option>license</option>
			<option>lot</option>
			<option>lot_delta</option>
			<option>section</option>
			<option>variety</option>
		</datalist>
	</div>
	<div>
		<input autocomplete="off" autofocus name="q" placeholder="search" value="<?= h($_GET['q']) ?>">
	</div>
	<div>
		<input name="dt0" placeholder="after" type="date" value="<?= h($_GET['dt0']) ?>">
	</div>
	<div>
		<input name="dt1" placeholder="before" type="date" value="<?= h($_GET['dt1']) ?>">
	</div>
	<div>
		<button type="submit">Go</button>
	</div>
	<div>
		<button formtarget="_blank" name="a" type="submit" value="snap">Snap</button>
	</div>
	<div style="padding: 0 0.50rem;">
		<a href="?<?= $link_back ?>">Back</a> | <a href="?<?= $link_next ?>">Next</a>
	</div>
</div>
</form>

<div class="sql-debug"><?= h(trim($data['sql_debug'])) ?></div>

<table>
<thead>
	<tr>
		<th></th>
		<th>Date</th>
		<th>Command</th>
		<th>Subject</th>
	</tr>
</thead>
<tbody>
<?php
$idx = $offset;
foreach ($data['log_delta'] as $rec) {

	$idx++;

	$dt0 = new \DateTime($rec['created_at']);
	$dt0->setTimezone($tz);

	printf('<tr class="tr1" id="row-%d-1">', $idx);
	printf('<td class="c"><a href="/log?id=%s">%d</a></td>', $rec['id'], $idx);
	printf('<td title="%s">%s</td>', h($rec['created_at']), $dt0->format('m/d H:i:s'));
	printf('<td>%s</td>', $rec['command']);
	printf('<td>%s:%s</td>', $rec['subject'], $rec['subject_id']);
	// echo '<td>' . h($req) . '</td>';
	// echo '<td>' . h($res) . '</td>';
	// echo '<td class="r">' . strlen($rec['res_body']) . ' bytes</td>';
	echo '</tr>';

	// Show Diff
	$v0 = json_decode($rec['v0'], true);
	$v1 = json_decode($rec['v1'], true);

	$diff = __array_diff_keyval_r($v0, $v1);

	$key_list = array_keys($diff);
	sort($key_list);

	printf('<tr class="tr2" id="row-%d-2">', $idx);
	echo '<td></td>';
	echo '<td colspan="3" style="padding:0;">';

		echo '<table>';
		echo '<thead><tr><th>Key</th><th>Old</th><th>New</th></tr></thead>';
		echo '<tbody>';

		foreach ($key_list as $k) {

			$v0 = $diff[$k]['old'];
			$v1 = $diff[$k]['new'];

			if (is_array($v0) || is_array($v1)) {
				_echo_sub_diff($k, $v0, $v1);
			} else {
				echo '<tr>';
				printf('<td>%s</td>', $k);
				printf('<td class="old">%s</td>', $v0);
				printf('<td class="new">%s</td>', $v1);
				echo '</tr>';
			}

		}
		echo '</tbody>';
		echo '</table>';

	echo '</td>';
	echo '</tr>';

}
?>
</tbody>
</table>

<script src="https://cdn.openthc.com/zepto/1.2.0/zepto.js" integrity="sha256-vrn14y7WH7zgEElyQqm2uCGSQrX/xjYDjniRUQx3NyU=" crossorigin="anonymous"></script>

<script>
function rowOpen(row)
{
	var id1 = row.id;
	var id2 = id1.replace(/-1$/, '-2');

	var tr2 = $('#' + id2);

	tr2.show();

	row.setAttribute('data-mode', 'open');


}

function rowShut(row)
{
	var id1 = row.id;
	var id2 = id1.replace(/-1$/, '-2');

	var tr2 = $('#' + id2);

	tr2.hide();

	row.setAttribute('data-mode', 'shut');
}

$(function() {

	$('.tr1').on('click', function() {

		var id1 = this.id;
		var id2 = id1.replace(/-1$/, '-2');

		var mode = this.getAttribute('data-mode');
		if ('open' == mode) {
			rowShut(this);
			return false;
		}

		rowOpen(this);

	});

	var hash = window.location.hash;
	hash = hash.replace(/#/, '');
	var rec_list = hash.split(',');
	rec_list.forEach(function(v, i) {
		var key = `#row-${v}-1`;
		var row = document.querySelector(key);
		if (row) {
			rowOpen(row);
		}
	});

});
</script>
</body>
</html>

<?php
/**
 * Sanatize REQ or RES
 */
function _view_data_scrub($x)
{
	$x = preg_replace('/^x\-mjf\-key:.+$/im', 'x-mjf-key: **redacted**', $x);
	$x = preg_replace('/^authorization:.+$/im', 'authorization: **redacted**', $x);

	$x = preg_replace('/^set\-cookie:.+$/im', 'set-cookie: **redacted**', $x);

	$x = preg_replace('/"transporter_name1":\s*"[^"]+"/im', '"transporter_name1":"**redacted**"', $x);
	$x = preg_replace('/"transporter_name2":\s*"[^"]+"/im', '"transporter_name2":"**redacted**"', $x);

	$x = preg_replace('/"vehicle_license_plate":\s*"[^"]+"/im', '"vehicle_license_plate":"**redacted**"', $x);
	$x = preg_replace('/"vehicle_vin":\s*"[^"]+"/im', '"vehicle_license_plate":"**redacted**"', $x);

	return $x;

}

function _echo_sub_diff($pre, $v0, $v1)
{
	if (empty($v0)) {
		$v0 = [];
	}
	if (empty($v1)) {
		$v1 = [];
	}

	$key_list = [];
	if (is_array($v0)) {
		$key_list = array_keys($v0);
	}

	if (is_array($v1)) {
		$key_list += array_keys($v1);
	}

	sort($key_list);

	// var_dump($key_list); exit;
	foreach ($key_list as $k) {

		$v0v = $v0[$k];
		$v1v = $v1[$k];

		if (is_array($v0v) || is_array($v1v)) {
			_echo_sub_diff($k, $v0v, $v1v);
		} else {
			echo '<tr>';
			printf('<td>%s -&gt; %s</td>', $pre, $k);
			printf('<td class="old">%s</td>', $v0v);
			printf('<td class="new">%s</td>', $v1v);
			echo '</tr>';
		}
	}

}
