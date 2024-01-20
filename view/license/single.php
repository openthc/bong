<?php
/**
 * License Single View
 *
 * SPDX-License-Identifier: MIT
 */

?>

<div class="container">

<h1>
	License :: <?= __h($data['name']) ?>
	<a class="btn btn-sm" href="<?= sprintf('%s/license/%s', \OpenTHC\Config::get('openthc/dir/origin'), $data['id']) ?>" target="_blank"><i class="fa-regular fa-address-book"></i></a>
</h1>

<form action="<?= sprintf('/license/%s/verify', $data['id']) ?>" method="post">
<div class="mb-2">
	<a class="btn btn-primary" href="?object-status=show">Show Database Object Status</a>
	<!-- <button class="btn btn-warning" name="a" value="license-object-status-update"> Update Object Status</button> -->
	<button class="btn btn-warning" name="a" value="license-verify">Verify</button>
	<button class="btn btn-secondary" name="a" value="license-cache-clear">Clear Cache</button>

</div>
</form>

<div class="mb-2">
	<pre><?= __h(print_r($data, true)) ?></pre>
</div>

</div>
