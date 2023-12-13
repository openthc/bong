<?php
/**
 * OpenTHC HTML Layout
 *
 * SPDX-License-Identifier: MIT
 */

use Edoceo\Radix\Session;

// $page = [];
// $page = $data['Page'];

if (empty($_ENV['title'])) {
	$_ENV['title'] = $this->data['Page']['title'];
}
if (empty($_ENV['h1'])) {
	$_ENV['h1'] = $data['h1'];
}
if (empty($_ENV['title'])) {
	$_ENV['title'] =$_ENV['h1'];
}

?>
<!DOCTYPE html>
<html lang="en" translate="no">
<head>
<meta charset="utf-8">
<meta name="application-name" content="OpenTHC">
<meta name="viewport" content="initial-scale=1, user-scalable=yes">
<meta name="theme-color" content="#003100">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="google" content="notranslate">
<link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css" integrity="sha256-CTSx/A06dm1B063156EVh15m6Y67pAjZZaQc89LLSrU=">
<link rel="stylesheet" href="/vendor/jquery-ui/jquery-ui.min.css" integrity="sha256-VNxxeWv78fBpVZ3cM8LomS7+xUH2IXl6hJ1EKmmCJpY=">
<link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css" integrity="sha256-MBffSnbbXwHCuZtgPYiwMQbfE7z+GOZ7fBPCNB06Z98=">
<link rel="stylesheet" href="https://cdn.openthc.com/css/www/0.0.1/www.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="/css/app.css">
<title><?= __h(strip_tags($_ENV['title'])) ?></title>
</head>
<body>

<?= $this->block('menu-zero') ?>

<?php

if (!empty($_ENV['h1'])) {
	echo '<h1>';
	echo $_ENV['h1'];
	if (!empty($_ENV['h1-sub'])) {
		echo sprintf(' <small>%s</small>', $_ENV['h1-sub']);
	}
	echo '</h1>';
}


$x = Session::flash();
if (!empty($x)) {

	$x = str_replace('<div class="good">', '<div class="alert alert-success" role="alert">', $x);
	$x = str_replace('<div class="info">', '<div class="alert alert-info" role="alert">', $x);
	$x = str_replace('<div class="warn">', '<div class="alert alert-warning" role="alert">', $x);
	$x = str_replace('<div class="fail">', '<div class="alert alert-danger" role="alert">', $x);

	echo '<div class="radix-flash">';
	echo $x;
	echo '</div>';

}

echo $this->body;

echo $this->block('footer');

?>

<script src="/vendor/jquery/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="/vendor/jquery-ui/jquery-ui.min.js" integrity="sha256-lSjKY0/srUM9BE3dPm+c4fBo1dky2v27Gdjm2uoZaL0=" crossorigin="anonymous"></script>
<script src="/vendor/bootstrap/bootstrap.bundle.min.js" integrity="sha256-gvZPYrsDwbwYJLD5yeBfcNujPhRoGOY831wwbIzz3t0=" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="/vendor/htmx/htmx.min.js" integrity="sha256-lqM0qVcKOCz5xhofhtVYcLocZeFmzFvK6Y3djNq+uIY=" crossorigin="anonymous"></script>
<?= $this->foot_script ?>
</body>
</html>
