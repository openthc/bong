<?php
/**
 * BONG Menu
 *
 * SPDX-License-Identifier: MIT
 */

?>

<nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top">
<div class="container-fluid">

<a class="navbar-brand" href="/">
	<img alt="OpenTHC Logo" src="https://openthc.com/img/icon.png" style="height:32px;">
</a>

<button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu-zero" aria-expanded="false" aria-controls="menu-zero">
	<span class="navbar-toggler-icon"></span>
</button>

<div class="navbar-collapse collapse" id="menu-zero">

	<ul class="navbar-nav">

		<li class="nav-item">
			<a class="nav-link" href="/browse">Browse</a>
		</li>

		<li class="nav-item">
			<a class="nav-link" href="/log" title="Log Viewer">Logs</a>
		</li>

	</ul>

	<div class="mx-auto">
		<form action="/search" class="form-inline">
		<div class="input-group">
			<input class="form-control" name="q" placeholder="Search..." type="text">
			<button class="btn btn-outline-success" type="submit"><i class="fas fa-search"></i></button>
		</div>
		</form>
	</div>

	<ul class="navbar-nav navbar-nav-two ms-auto">
		<li class="nav-item">
			<a class="btn nav-link" href="/auth/shut"><i class="fas fa-power-off"></i></a>
		</li>
	</ul>

</div>

</div>
</nav>
