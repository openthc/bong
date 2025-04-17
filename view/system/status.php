<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

// Upload Lag Time
// select avg(now() - created_at) AS avg FROM log_upload WHERE created_at >= '2025-02-18' AND created_at < '2025-03-01' AND stat IN (102);

// Upload Response Time
// select avg(updated_at - created_at) AS avg FROM log_upload WHERE created_at >= '2025-02-18' AND created_at < '2025-03-01' AND stat IN (200, 202, 400, 404);


?>

<div class="container-fluid">
<h1>Global Object Status</h1>

<?php
if (empty($_SESSION['License']['id'])) {
?>
	<section>
		<h2>Export / Import Processing</h2>
		<div hx-get="/system/ajax?a=request-processing" hx-trigger="load delay:2s, every 30s">
			<strong>Loading...</strong>
		</div>
	</section>

	<hr>

<?php
}
?>

<section>
<h2>Object Status / Errors / Counts</h2>

	<?php
	if (empty($_SESSION['License']['id'])) {
	?>
		<div>
			<h3>License Information <a href="/license/status?v=full">/license/status?v=full</a></h3>
			<div class="row" hx-get="/license/status" hx-trigger="load delay:4s, every 30s, queue:all">
				<strong>Loading...</strong>
			</div>
		</div>
	<?php
	}
	?>

	<div>
		<h3>Section Information</h3>
		<div class="row" hx-get="/section/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

	<div>
		<h3>Variety Information</h3>
		<div class="row" hx-get="/variety/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

	<div>
		<h3>Product Information</h3>
		<div class="row" hx-get="/product/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

	<div>
		<h3>Crop Information</h3>
		<div class="row" hx-get="/crop/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

	<div>
		<h3>Inventory Information</h3>
		<div class="row" hx-get="/inventory/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

	<div>
		<h3>Inventory Adjust Information</h3>
		<div class="row" hx-get="/inventory-adjust/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

	<div>
		<h3>B2B Incoming Information</h3>
		<div class="row" hx-get="/b2b/incoming/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

	<div>
		<h3>B2B Outgoing Information</h3>
		<div class="row" hx-get="/b2b/outgoing/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</div>

</section>

</div>
