<?php
/**
 * License Single View
 *
 * SPDX-License-Identifier: MIT
 */

?>

<div class="container">

<h1>License :: <?= __h($data['name']) ?></h1>

<div class="mb-2">
	<pre>
	<?= __h(print_r($data, true)) ?>
	</pre>
</div>

<form action="<?= sprintf('/license/%s/verify', $data['id']) ?>" method="post">
<div class="mb-2">
	<a class="btn btn-primary" href="?object-status=show">Show Database Object Status</a>
	<!-- <button class="btn btn-warning" name="a" value="license-object-status-update"> Update Object Status</button> -->
	<button class="btn btn-warning" name="a" value="license-verify"> Verify</button>

</div>
</form>

</div>
