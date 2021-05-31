<?php
/**
 * View the Delta Log
 */

$tz = new \DateTimezone($data['tz']);

?>

<h1><?= $data['Page']['title'] ?></h1>

<form autocomplete="off">
<div class="search-filter">
	<div>
		<a class="btn btn-sm btn-outline-secondary" href="/browse">Browse <i class="far fa-folder-open"></i></a>
	</div>
	<div>
		<input class="form-control form-control-sm" list="subject-list" name="subject" placeholder="Type to search...">
		<datalist id="subject-list">
			<option>- any -</option>
			<option>license</option>
			<option>section</option>
			<option>product</option>
			<option>variety</option>
			<option>crop</option>
			<option>lot</option>
			<option>lab_result</option>
			<option>b2b_sale</option>
			<option>b2c_sale</option>
			<option>lot_delta</option>
			<option>disposal</option>
		</datalist>
	</div>
	<div>
		<input autocomplete="off" autofocus class="form-control form-control-sm" name="q" placeholder="search" value="<?= h($_GET['q']) ?>">
	</div>
	<div>
		<input class="form-control form-control-sm" name="d0" type="date" value="<?= h($_GET['d0']) ?>">
	</div>
	<div>
		<input class="form-control form-control-sm" name="t0" type="time" value="<?= h($_GET['t0']) ?>">
	</div>
	<div>
		<input class="form-control form-control-sm" name="d1" type="date" value="<?= h($_GET['d1']) ?>">
	</div>
	<div>
		<input class="form-control form-control-sm" name="t1" type="time" value="<?= h($_GET['t1']) ?>">
	</div>
	<div>
		<button class="btn btn-sm btn-outline-secondary" type="submit">Go <i class="fas fa-search"></i></button>
	</div>
	<div>
		<button class="btn btn-sm btn-outline-secondary" formtarget="_blank" name="a" type="submit" value="snap">Snap <i class="fas fa-file-export"></i></button>
	</div>
	<div>
		<div class="btn-group btn-group-sm">
			<a class="btn btn-sm btn-outline-secondary" href="?<?= $data['link_newer'] ?>"><i class="fas fa-arrow-left"></i> Newer</a>
			<a class="btn btn-sm btn-outline-secondary" href="?<?= $data['link_older'] ?>">Older <i class="fas fa-arrow-right"></i></a>
		</div>
	</div>
</div>
</form>

<pre class="sql-debug"><?= h(trim($data['sql_debug'])) ?></pre>

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

	// Show Diff
	$v0 = json_decode($rec['v0'], true);
	$v1 = json_decode($rec['v1'], true);

	$diff = __array_diff_keyval_r($v0, $v1);
	__ksort_r($diff);

	// Lead Row
	printf('<tr class="tr1" id="row-%d-1">', $idx);
	printf('<td class="c"><a href="/log?id=%s">%d</a></td>', $rec['id'], $idx);
	printf('<td title="%s">%s</td>', h($rec['created_at']), $dt0->format('m/d H:i:s'));
	printf('<td>%s</td>', $rec['command']);
	printf('<td>%s:%s</td>', $rec['subject'], $rec['subject_id']);
	echo '</tr>';

	// Diff Row
	printf('<tr class="tr2" id="row-%d-2">', $idx);
	echo '<td></td>';
	echo '<td colspan="3" style="padding:0;">';

		echo '<table class="diff table-hover">';
		echo '<thead><tr><th class="key">Key</th><th class="old">Old</th><th class="new">New</th></tr></thead>';
		echo '<tbody>';

		_echo_diff('$', $diff);

		echo '</tbody>';
		echo '</table>';

	echo '</td>';
	echo '</tr>';

}
?>
</tbody>
</table>


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


<?php
/**
 * Echo the DIFF
 */
function _echo_diff($p0, $d0)
{
	if (!is_array($d0)) {
		var_dump($d0); exit;
	}

	$key_list = array_keys($d0);
	sort($key_list);

	foreach ($key_list as $k0) {

		$k1 = sprintf('%s.%s', $p0, $k0);
		$v0 = $d0[$k0];

		if (array_key_exists('old', $v0) && array_key_exists('new', $v0)) {
			echo '<tr>';
			printf('<td class="key">%s</td>', $k1);
			printf('<td class="old">%s</td>', $v0['old']);
			printf('<td class="new">%s</td>', $v0['new']);
			echo '</tr>';
		} else {
			_echo_diff($k1, $v0);
		}

	}

}


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
