<?php
/**
 * OpenTHC HTML Layout
 */

use Edoceo\Radix\Session;

if (empty($_ENV['title'])) {
	$_ENV['title'] = $this->data['Page']['title'];
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css" integrity="sha512-HK5fgLBL+xu6dm/Ii3z4xhlSUyZgTT9tuc/hSrtw6uzJOvgRr2a9jyxxT1ely+B+xFAmJKVSTbpM/CuL7qxO8w==" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css" integrity="sha512-P5MgMn1jBN01asBgU0z60Qk4QxiXo86+wlFahKrsQf37c9cro517WzVSPPV1tDKzhku2iJ2FVgL67wG03SGnNA==" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.openthc.com/css/www/0.0.1/www.css">
<title><?= h(strip_tags($_ENV['title'])) ?></title>
</head>
<body>
<?= $this->block('menu-navbar') ?>
<div class="container-fluid">
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

echo '</div>'; // .container-fluid

echo $this->block('footer');

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha512-uto9mlQzrs59VwILcLiRYeLKPPbS/bT71da/OEBYEwcdNUk8jYIy+D176RYoop1Da+f9mvkYrmj5MCLZWEtQuA==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js" integrity="sha512-XKa9Hemdy1Ui3KSGgJdgMyYlUg1gM+QhL6cnlyTe2qzMCYm4nAZ1PsVerQzTTXzonUR+dmswHqgJPuwCq1MaAg==" crossorigin="anonymous"></script>
<?= $this->foot_script ?>
</body>
</html>
