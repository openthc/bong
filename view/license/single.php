<?php
/**
 * License Single View
 *
 * SPDX-License-Identifier: MIT
 */

$_ENV['title'] = sprintf('License :: %s', __h($data['name']));

if ('status-cache' == $_GET['view']) {
	require_once(__DIR__ . '/single-status-cache.php');
	exit(0);
}

?>

<div class="container">

<div class="d-flex justify-content-between">
	<div>
		<h1 data-blur="true">License :: <code><?= __h($data['code']) ?></code> <?= __h($data['name']) ?></h1>
	</div>
	<div>
		<h2><?= $data['stat'] ?></h2>
	</div>
</div>


<form method="post">
<div class="mb-2">
	<?php
	if ( ! empty($data['company_profile_url'])) {
		printf('<a class="btn btn-secondary" href="%s" target="_blank">CMP</a>', $data['company_profile_url']);
	}
	?>
	<a class="btn btn-secondary" href="<?= sprintf('%s/license/%s', \OpenTHC\Config::get('openthc/dir/origin'), $data['id']) ?>" target="_blank"><i class="fa-regular fa-address-book"></i></a>
	<a class="btn btn-primary" href="?object-status=show">Show Database Object Status</a>
	<!-- <button class="btn btn-warning" name="a" value="license-object-status-update"> Update Object Status</button> -->
	<button class="btn btn-secondary" name="a" value="license-cache-clear">Cache Clear</button>
	<button class="btn btn-warning" name="a" value="license-verify">Verify</button>
	<button class="btn btn-danger" name="a" value="license-error-reset">Error Reset</button>
</div>
</form>


<hr>


<section>
	<h2>Object Status :: Cache</h2>
	<div hx-get="?view=status-cache" hx-trigger="load delay:30s, every 30s, queue:none">
	<?php
	require_once(__DIR__ . '/single-status-cache.php');
	?>
	</div>
</section>

<div id="status-sync-wrap" hx-sync="#status-sync-wrap:queue all">
<?php
$data['object-status'] = true;
if ( ! empty($data['object-status'])) {
?>
	<hr>

	<h2>Object Status :: Database</h2>
	<section>
		<h3>Section Information</h3>
		<?= _htmx_delay_load(sprintf('/section/status?license=%s', $data['id'])) ?>
	</section>

	<section>
		<h3>Variety Information</h3>
		<?= _htmx_delay_load(sprintf('/variety/status?license=%s', $data['id'])) ?>
	</section>

	<section>
		<h3>Product Information</h3>
		<?= _htmx_delay_load(sprintf('/product/status?license=%s', $data['id'])) ?>
	</section>

	<section>
		<h3>Crop Information</h3>
		<?= _htmx_delay_load(sprintf('/crop/status?license=%s', $data['id'])) ?>
	</section>

	<section>
		<h3>Inventory Information</h3>
		<?= _htmx_delay_load(sprintf('/inventory/status?license=%s', $data['id'])) ?>
	</section>
<!--
	<section>
		<h3>Inventory Adjust Information</h3>
		<?= _htmx_delay_load(sprintf('/inventory-adjust/status?license=%s', $data['id'])) ?>
	</section>
 -->
	<section>
		<h3>B2B Incoming Information</h3>
		<?= _htmx_delay_load(sprintf('/b2b/incoming/status?license=%s', $data['id'])) ?>
	</section>

	<section>
		<h3>B2B Outgoing Information</h3>
		<?= _htmx_delay_load(sprintf('/b2b/outgoing/status?license=%s', $data['id'])) ?>
	</section>

<?php
}
?>
</div>

<!--
<div class="mb-2">
	<pre><?= __h(print_r($data, true)) ?></pre>
</div>
-->

</div>


<?php
function _apply_object_sync_status_style($val) : string {

	switch (intval($val)) {
	case 0:
	case 1:
		return sprintf('<span class="text-danger" title="No Data / Bad Data">%03d</span>', $val);
	case 100:
		return sprintf('<span class="text-success" title="Should Process CSV Create">%03d</span>', $val);
	case 102:
		return sprintf('<span class="text-warning" title="Upload/Update Pending">%03d</span>', $val);
	case 200:
	case 202:
		return sprintf('<span class="text-primary">%03d</span>', $val);
	}

	return sprintf('%03d', $val);

}

function _htmx_delay_load($url) : string {

	$ret = [];
	$ret[] = sprintf('<div class="row" hx-get="%s" hx-trigger="load delay:2s, every 30s, queue:all">', $url);
	$ret[] = '<strong><i class="fa-solid fa-arrows-rotate fa-spin"></i> Loading...</strong>';
	$ret[] = '</div>';

	return implode('', $ret);
}
