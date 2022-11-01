#!/usr/bin/php
<?php
/**
 * This routine simply authenticates to the CCRS platform and stores the cookies
 * The CCRS system appears to have a 15 minute timeout.
 *
 * SPDX-License-Identifier: MIT
 */

require_once(__DIR__ . '/../boot.php');

$u = \OpenTHC\Config::get('cre/usa/wa/ccrs/username');
$p = \OpenTHC\Config::get('cre/usa/wa/ccrs/password');

$cre = new \OpenTHC\CRE\CCRS([]);

$cookie_data = $cre->auth($u, $p);

// Save
$f = sprintf('%s/var/ccrs-cookies.json', APP_ROOT);
$d = json_encode($cookie_data, JSON_PRETTY_PRINT);
$x = file_put_contents($f, $d);
if (false === $x) {
	echo "Error writing to: $f\n";
	exit(1);
}

exit(0);
