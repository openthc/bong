<?php
/**
 * Browse Data
 *
 * SPDX-License-Identifier: MIT
 */

?>

<div class="container">

<h1>BONG</h1>
<p>Browse the object-interfaces available via the <em>BONG</em> service</p>

<section class="mb-4">
<h2>Selected License</h2>
<form action="/auth/open" autocomplete="off" method="post">
<div>
<div class="input-group">
	<?php
	if (!empty($_SESSION['license-list'])) {
		echo '<select class="form-control" name="license">';
		foreach ($_SESSION['license-list'] as $l) {
			$val = $l['License']['Number'];
			$sel = ($val == $_SESSION['cre-auth']['license'] ? ' selected' : null);
			$txt = h(sprintf('%s #%s', $l['Name'], $val));
			echo sprintf('<option%s>%s</option>', $css, $val, $txt);
		}
		echo '</select>';
	} else {
		echo sprintf('<input autocomplete="off" class="form-control" name="license" value="%s">', h($data['cre_meta_license']));
	}
	?>
	<button class="btn btn-outline-secondary" name="a" value="set-license"><i class="fas fa-save"></i></button>
</div>
<span class="form-text">These systems require a license for many, if not all API calls, see /license for possible values.</span>
</div>
</form>
</section>


<section class="mb-4">

	<h2>Synchronization</h2>
	<form method="post">
	<div>
		<button class="btn btn-outline-secondary" name="a" value="sync">Sync <i class="fas fa-sync"></i></button>
	</div>
	</form>

<pre><?php
$sync_info = [];
foreach ($data['cre_sync'] as $x) {
	$sync_info[ $x['key'] ] = $x['val'];
}
$t0 = $sync_info['sync-time-alpha'];
unset($sync_info['sync-time-alpha']);
$t1 = $sync_info['sync-time-omega'];
unset($sync_info['sync-time-omega']);

$fmt = "% -30s == %s\n";

printf($fmt, 'sync-time-alpha', $t0);
foreach ($sync_info as $k => $v) {
	printf($fmt, $k, $v);
}
printf($fmt, 'sync-time-omega', $t1);
?></pre>

</section>


<div class="mb-4">
<h2>Auth</h2>
<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/auth/open?r=%2Fbrowse">/auth/open</a> - Authenticate</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/auth/ping">/auth/ping</a> - Authentication and Session Information</div>
	<div>
		<span class="badge bg-primary">OpenTHC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/auth/shut">/auth/shut</a> - Close Session</div>
	<div>
		<span class="badge bg-primary">OpenTHC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/system">/system</a> - View System Information</div>
	<div>
		<span class="badge bg-primary">OpenTHC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Core Data</h2>
<p>These are the core system and company specific data options.</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/company/current">/company</a> - Your Company Information</div>
	<div>
		<span class="badge bg-dark">system</span>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-secondary">CCRS</span>
		<span class="badge bg-secondary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/contact/current">/contact</a> - Contacts, Drivers, Employees</div>
	<div>
		<span class="badge bg-dark">system</span>
		<span class="badge bg-warning">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-secondary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/license/current">/license</a> - Your License Information</div>
	<div>
		<span class="badge bg-dark">system</span>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/license/type">/license/type</a> - List all License Type Information</div>
	<div>
		<span class="badge bg-dark">system</span>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-warning">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/section">/section</a> - an Area or Room or Zone</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/variety">/variety</a> - Variety (aka: Cultivar, Strains)</div>
	<div>
		<span class="badge bg-warning">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/product/type">/product/type</a> - System Defined Product Type Details</div>
	<div>
		<span class="badge bg-dark">system</span>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/product">/product</a> - Product Details</div>
	<div>
		<span class="badge bg-warning">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/vehicle">/vehicle</a> - Vehicles</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-warning">CCRS</span>
		<span class="badge bg-secondary">METRC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Batches</h2>
<p>In some systems Batches are logical containers for Plants and Lots.</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div> <a href="/batch">/batch</a> - Batch Details</div>
	<div>
		<span class="badge bg-secondary">BioTrack</span>
		<span class="badge bg-dark">CCRS</span>
		<span class="badge bg-warning">METRC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Crop / Plants</h2>
<p>Plants, Plants in Rooms, Harvest (wet-collect) and Cure (dry-collect) operations.</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div> <a href="/crop">/crop</a> - Crop Details</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div> <a href="/crop/collect">/crop/collect</a> - Plant Collection Details</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Inventory / Lots</h2>
<p>Bulk Materials, Conversion, Production Lots, Conversion (again), Packaged Lots</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/inventory">/inventory</a> - Inventory Lot Details</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/inventory-adjust">/inventory-adjust</a> - Inventory Adjust Details</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/disposal">/disposal</a> - Inventory Waste and Disposal Details</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-secondary">METRC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Quality Assurance</h2>
<p>Lab Samples and Results</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/lab">/lab</a> - Inventory Lab Samples and Results</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-warning">METRC</span>
	</div>
</li>
</ul>
</div>

<div class="mb-4">
<h2>B2B Sales</h2>
<p>B2B Sales, (aka: Transfers, Manifests)</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/b2b/outgoing">/b2b/outgoing</a> - B2B Sales</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-warning">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/b2b/incoming">/b2b/incoming</a> - B2B Sales to Accept</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-warning">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/b2b/rejected">/b2b/rejected</a> - Rejects/Returns</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-warning">METRC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>B2C Sales</h2>
<p>Individual Sales Transaction Records</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/b2c">/b2c</a> - B2C Sales</div>
	<div>
		<span class="badge bg-primary">BioTrack</span>
		<span class="badge bg-primary">CCRS</span>
		<span class="badge bg-primary">METRC</span>
	</div>
</li>
</ul>
</div>

</div>
