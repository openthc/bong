<?php
/**
 *
 */

 ?>

<div class="container">

<h1>BONG</h1>
<p>Browse the object-interfaces available via the <em>BONG</em> service</p>

<div class="mb-4">
<h2>Auth</h2>
<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/auth/open?r=%2Fbrowse">/auth/open</a> - Authenticate</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/auth/ping">/auth/ping</a> - Authentication and Session Information</div>
	<div>
		<span class="badge badge-primary">OpenTHC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/auth/shut">/auth/shut</a> - Close Session</div>
	<div>
		<span class="badge badge-primary">OpenTHC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/system">/system</a> - View System Information</div>
	<div>
		<span class="badge badge-primary">OpenTHC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Selected License</h2>
<form action="/auth/open" method="post">
<div class="form-group">
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
		echo sprintf('<input class="form-control" name="license" value="%s">', h($data['cre_meta_license']));
	}
	?>
	<div class="input-group-append">
		<button class="btn btn-outline-secondary" name="a" value="set-license"><i class="fas fa-save"></i></button>
	</div>
</div>
<span class="form-text">These systems require a license for many, if not all API calls, see /license for possible values.</span>
</div>
</form>
</div>


<div class="mb-4">
<h2>Core Data</h2>
<p>These are the core system and company specific data options.</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/company">/company</a> - Your Company Information</div>
	<div>
		<span class="badge badge-dark">system</span>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-secondary">LeafData</span>
		<span class="badge badge-secondary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/contact">/contact</a> - Contacts, Drivers, Employees</div>
	<div>
		<span class="badge badge-dark">system</span>
		<span class="badge badge-warning">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-secondary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/license-type">/license-type</a> - List all License Information</div>
	<div>
		<span class="badge badge-dark">system</span>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/license">/license</a> - Your License Information</div>
	<div>
		<span class="badge badge-dark">system</span>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-warning">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/product-type">/product-type</a> - System Defined Product Type Details</div>
	<div>
		<span class="badge badge-dark">system</span>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/product">/product</a> - Product Details</div>
	<div>
		<span class="badge badge-warning">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/variety">/variety</a> - Variety (aka: Cultivar, Strains)</div>
	<div>
		<span class="badge badge-warning">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/vehicle">/vehicle</a> - Vehicles</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-warning">LeafData</span>
		<span class="badge badge-secondary">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/section">/section</a> - an Area or Room or Zone</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Batches</h2>
<p>In most systems Batches are logical containers for Plants and Lots.
Notably in LeafData the Batch also contains plant collection details.
</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div> <a href="/batch">/batch</a> - Batch Details</div>
	<div>
		<span class="badge badge-secondary">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-warning">METRC</span>
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
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div> <a href="/crop/collect">/crop/collect</a> - Plant Collection Details</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
</ul>
</div>


<div class="mb-4">
<h2>Lots / Inventory</h2>
<p>Bulk Materials, Conversion, Production Lots, Conversion (again), Packaged Lots</p>

<ul class="list-group">
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/lot">/lot</a> - Inventory Lot Details</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/lot/history">/lot/history</a> - Inventory Lot History (Adjustment) Details</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/disposal">/disposal</a> - Inventory Waste and Disposal Details</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-secondary">METRC</span>
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
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-warning">METRC</span>
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
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-warning">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/b2b/incoming">/b2b/incoming</a> - B2B Sales to Accept</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-warning">METRC</span>
	</div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
	<div><a href="/b2b/rejected">/b2b/rejected</a> - Rejects/Returns</div>
	<div>
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-warning">METRC</span>
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
		<span class="badge badge-success">BioTrack</span>
		<span class="badge badge-success">LeafData</span>
		<span class="badge badge-success">METRC</span>
	</div>
</li>
</ul>
</div>

</div>
