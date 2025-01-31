<?php
/**
 *
 *
 */

?>

<div class="container-fluid">

<?php
if (empty($_SESSION['License']['id'])) {
?>
	<section>
		<h2>File Processing:</h2>
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
		<section>
			<h3>License Information <a href="/license/status?v=full">/license/status?v=full</a></h3>
			<div hx-get="/license/status" hx-trigger="load delay:4s, every 30s, queue:all">
				<strong>Loading...</strong>
			</div>
		</section>
	<?php
	}
	?>

	<section>
		<h3>Section Information</h3>
		<div hx-get="/section/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

	<section>
		<h3>Variety Information</h3>
		<div hx-get="/variety/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

	<section>
		<h3>Product Information</h3>
		<div hx-get="/product/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

	<section>
		<h3>Crop Information</h3>
		<div hx-get="/crop/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

	<section>
		<h3>Inventory Information</h3>
		<div hx-get="/inventory/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

	<section>
		<h3>Inventory Adjust Information</h3>
		<div hx-get="/inventory-adjust/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

	<section>
		<h3>B2B Incoming Information</h3>
		<div hx-get="/b2b/incoming/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

	<section>
		<h3>B2B Outgoing Information</h3>
		<div hx-get="/b2b/outgoing/status" hx-trigger="load delay:4s, every 30s, queue:all">
			<strong>Loading...</strong>
		</div>
	</section>

</section>

</div>
