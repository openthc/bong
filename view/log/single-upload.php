<?php
/**
 * View the Logs from the Bong
 *
 * SPDX-License-Identifier: MIT
 */

$dtX = new \DateTime();
$tz0 = new \DateTimezone('America/Los_Angeles');

$res = $data['log_upload'];

$dt_list = [];

$dtC = new DateTime($res['created_at']);
$dtC->setTimezone($tz0);
$dt_list[] = [
	'dt' => $dtC,
	'name' => 'Created',
];

$dtU = new DateTime($res['updated_at']);
$dtU->setTimezone($tz0);
$dt_list[] = [
	'dt' => $dtU,
	'name' => 'Updated',
];

// Up-Scale Legacy Format
if (is_string($res['result_data']['@upload'])) {

	$res['result_data']['@upload'] = [
		'data' => $res['result_data']['@upload'],
		'meta' => [
			'created_at' => '',
			'created_at_cre' => '',
		]
	];

	// $upload_time = null;
	if (preg_match('/(Your submission was received at (.+) Pacific Time)/', $res['result_data']['@upload']['data'], $m)) {
		$dtX = new DateTime($m[2], $tz0);
		$res['result_data']['@upload']['meta']['created_at_cre'] = $dtX->format(DateTime::RFC3339);
	}

}

$dt_up0 = $dt_up_cre = null;
if ( ! empty($res['result_data']['@upload']['meta']['created_at'])) {
	$dt_up0 = new DateTime($res['result_data']['@upload']['meta']['created_at']);
	$dt_list[] = [
		'dt' => $dt_up0,
		'name' => 'Uploaded (OpenTHC)'
	];
}
if ( ! empty($res['result_data']['@upload']['meta']['created_at_cre'])) {
	$dt_up0_cre = new DateTime($res['result_data']['@upload']['meta']['created_at_cre']);
	$dt_list[] = [
		'dt' => $dt_up0_cre,
		'name' => 'Uploaded (CRE)'
	];
}

$dt_res0 = $dt_res1 = $dt_res2 = null;
if ( ! empty($res['result_data']['@result-mail'])) {
	// Date: Fri, 13 Jan 2023 10:18:31 -0800
	if (preg_match('/Date: (.+)/', $res['result_data']['@result-mail'], $m)) {
		$dt_res0 = new DateTime($m[1]);
		$dt_res0->setTimezone($tz0);
		// var_dump($m);
	}
}
// @todo this field shold be name
$res['result_data']['@result-file']['name'] = $res['result_data']['@result-file']['name'] ?: $res['result_data']['@result-file']['file'];

if ( ! empty($res['result_data']['@result-file']['name'])) {
	if ( ! empty($res['result_data']['@result-file']['created_at_cre'])) {
		$dt_res1 = new DateTime($res['result_data']['@result-file']['created_at_cre']);
		$dt_res1->setTimezone($tz0);
	} else {
		// Copied from cre-ccrs-pull.php
		/**
		 * error-response-file from the LCB sometimes are missing the
		 * milliseconds portion of the time in the file name
		 * So we have to patch it so it parses the same as their "normal"
		 */
		$csv_time = preg_match('/(\w+_)?\w+_(\d+T\d+)\.csv/i', $res['result_data']['@result-file']['name'], $m) ? $m[2] : null;
		if (strlen($csv_time) == 15) {
			$csv_time = $csv_time . '000';
		}

		$dt_res1 = DateTime::createFromFormat('Ymd\TGisv', $csv_time, new DateTimeZone('America/Los_Angeles'));
	}


}

usort($dt_list, function($a, $b) {
	return ($a['dt'] > $b['dt']);
});

// echo '<pre>';
// var_dump($dt_list);
// echo '</pre>';

?>

<style>
textarea {
	font-family: monospace;
	height: 22ex;
	overflow-wrap: normal;
	overflow-x: scroll;
	white-space: pre;
}
</style>

<!-- Snapshot Form -->
<form method="post" target="_blank">
	<div style="float: right; padding: 0.25rem;">
		<button class="btn btn-secondary" name="a" type="submit" value="log-snap">Snapshot</button>
	</div>
</form>


<div class="container-fluid">

<h1><?= __h($res['name']) ?> = <code><?= $res['stat'] ?></code></strong></h1>
<h2>License: <a href="/license/<?= $res['License']['id'] ?>"><?= __h($res['License']['name']) ?></a> <code><?= __h($res['License']['code']) ?></code></h2>

<table class="table table-sm table-bordered">
	<thead class="table-dark">
		<tr>
			<th>Action</th>
			<th>Timestamp</th>
			<th>Elapsed</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Created</td>
			<td title="<?= $res['created_at'] ?>"><?= $dtC->format(\DateTime::RFC3339) ?></td>
			<td>0:0:0</td>
		</tr>
		<tr>
			<td>Uploaded</td>
			<?php
			$dt1 = $dtC;
			if (empty($dt_up0)) {
				$dd = $dt1->diff($dtX);
				printf('<td>-</td><td class="text-secondary" title="elapsed time"><i>+%s</i></td>', $dd->format('%a %H:%I:%S'));
			} else {
				$dd = $dt1->diff($dt_up0);
				printf('<td>%s</td>', $dt_up0->format(\DateTime::RFC3339));
				printf('<td>+%s</td>', $dd->format('%a %H:%I:%S'));
				$dt1 = $dt_up0;
			}
			?>
		</tr>
		<tr>
			<td>Uploaded (CRE)</td>
			<?php
			if (empty($dt_up0_cre)) {
				$dd = $dt1->diff($dtX);
				printf('<td>-</td><td class="text-secondary" title="elapsed time"><i>+%s</i></td>', $dd->format('%a %H:%I:%S'));
			} else {
				$dd = $dt1->diff($dt_up0_cre);
				printf('<td>%s</td>', $dt_up0_cre->format(\DateTime::RFC3339));
				printf('<td>+%s</td>', $dd->format('%a %H:%I:%S'));
				$dt1 = $dt_up0_cre;
			}
			?>
		</tr>
		<tr>
			<td>Result File (CRE)</td>
			<?php
			if (empty($dt_res1)) {
				$dd = $dt1->diff($dtX);
				printf('<td>-</td><td class="text-secondary" title="elapsed time"><i>+%s</i></td>', $dd->format('%a %H:%I:%S'));
			} else {
				$dd = $dt1->diff($dt_res1);
				printf('<td>%s</td>', $dt_res1->format(\DateTime::RFC3339));
				printf('<td>+%s</td>', $dd->format('%a %H:%I:%S'));
				$dt1 = $dt_res1;
			}
			?>
		</tr>
		<tr>
			<td>Result Email (CRE)</td>
			<?php
			if (empty($dt_res0)) {
				$dd = $dt1->diff($dtX);
				printf('<td>-</td><td class="text-secondary"><i>+%s</i></td>', $dd->format('%a %H:%I:%S'));
			} else {
				$dd = $dt1->diff($dt_res0);
				printf('<td>%s</td>', $dt_res0->format(\DateTime::RFC3339));
				printf('<td>+%s</td>', $dd->format('%a %H:%I:%S'));
			}
			?>
		</tr>
		<tr>
			<td>Updated (DB)</td>
			<td title="<?= $res['updated_at'] ?>"><?= $dtU->format(\DateTime::RFC3339) ?></td>
			<td title="This value is the difference from the CREATED">+<?php
			$dd = $dtU->diff($dtC);
			echo $dd->format('%a %H:%I:%S');
			?>*</td>
		</tr>
		<tr>
			<td>Elapsed (DB)</td>
			<td>-</td>
			<td>+<?= $res['elapsed_ms'] ?></td>
		</tr>
	</tbody>
</table>



<hr>
<section>
	<h2>Source Data
		<code><?= __h($res['source_data']['name']) ?></code>
		<button class="btn btn-sm btn-primary data-download"
			data-source-data="#upload-source-data"
			data-source-name="<?= __h($res['source_data']['name']) ?>"><i class="fas fa-download"></i></button>
	</h2>
	<textarea class="form-control" id="upload-source-data"><?= __h($res['source_data']['data']); ?></textarea>
</section>

<hr>
<section>
	<h2>Upload Data</h2>
	<?php
	$upload = $res['result_data']['@upload'];
	if (is_array($upload)) {
		printf('<div class="alert alert-warning">Uploaded: %s (CRE); %s (OpenTHC)</div>'
			, $upload['meta']['created_at_cre']
			, $upload['meta']['created_at']
		);
	} elseif (is_string($upload)) {
		if (preg_match('/(Your submission was received at .+ Pacific Time)/', $upload_html, $m)) {
			printf('<div class="alert alert-warning">%s</div>', $m[1]);
		}
	}
	// if ( ! empty($res['result_data']['@upload'])) {
	// 	echo '<strong>Have HTML Upload Confirmation</strong>';
	// }
	?>
</section>


<hr>
<section>
<h2>Result Email</h2>
<?php
echo '<textarea class="form-control">';
echo __h($res['result_data']['@result-mail']);
echo '</textarea>';
?>
</section>

<hr>
<section>
<?php
$result = $res['result_data']['@result-file'];
$result_good = (! empty($result['data']) && ! empty($result['name']) );

echo '<h2>';
echo 'Result Data';
if ( ! empty($result['name'])) {
	printf(' <code>%s</code>', __h($result['name']));
}
if ($result_good) {
	printf(' <button class="btn btn-sm btn-primary data-download" data-source-data="#result-data" data-source-name="%s"><i class="fas fa-download"></i></button>', __h($result['name']));
}
echo '</h2>';

if (empty($result['data'])) {
	echo '<div class="alert alert-warning">No Response</div>';
} else {
	echo '<textarea class="form-control" id="result-data">';
	echo __h($result['data']);
	echo '</textarea>';
}
?>
</section>

<hr>

<?php
$res['source_data']['data'] = sprintf('%d bytes', strlen($res['source_data']['data']));
$res['result_data']['@upload']['data'] = sprintf('%d bytes', strlen($res['result_data']['@upload']['data']));
$res['result_data']['@result-file']['data'] = sprintf('%d bytes', strlen($res['result_data']['@result-file']['data']));
$res['result_data']['@result-mail'] = sprintf('%d bytes', strlen($res['result_data']['@result-mail']));
?>

<?php
if ( ! empty($_SESSION['_dump'])) {
?>
	<hr>

	<section>
	<h3>Record Dump</h3>
	<pre>
	<?= __h(print_r($res, true)) ?>
	</pre>
	</section>

<?php
}
?>

</div>


<script>
function b64(x)
{
	return window.btoa(x);
	// return window.btoa(unescape(encodeURIComponent(x)));
}

$(function() {
	$('button.data-download').on('click', function() {

		debugger;

		var source_node = this.getAttribute('data-source-data');
		var source_data = document.querySelector(source_node);
		var source_data = b64(source_data.innerHTML);

		var href = `data:text/plain;base64,${source_data}`;
		var name = this.getAttribute('data-source-name');

		var node = document.createElement('a');
		node.setAttribute('download', name);
		node.setAttribute('href', href);

		document.body.appendChild(node);
		node.click();

		document.body.removeChild(node);

		// document.location = data_url;
		// document.location = `data:text/attachment;base64,${body}`;
		// ' + // Notice the new "base64" bit!
		// utf8_to_b64(document.getElementById('hello').innerHTML);
		// data:text/plain;base64,<?= base64_encode($result['data']) ?>

		// return false;
	});
});
</script>
