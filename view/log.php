<?php
/**
 * View the Delta Log
 *
 * SPDX-License-Identifier: MIT
 */

$tz = new \DateTimezone($data['tz']);

?>

<div class="container-fluid">

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
			<option>inventory</option>
			<option>inventory_adjust</option>
			<option>lab_result</option>
			<option>b2b_sale</option>
			<option>b2c_sale</option>
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

<!-- <pre class="sql-debug"><?= h(trim($data['sql_debug'])) ?></pre> -->

<?php
// Log from Delta
if ( ! empty($data['log_delta'])) {
	require_once(__DIR__ . '/log-delta.php');
}

// Log for Upload Stuff
if ( ! empty($data['log_upload'])) {
	require_once(__DIR__ . '/log-upload.php');
}

?>

</div>
