#!/usr/bin/php
<?php
/**
 * This routine simply authenticates to the CCRS platform and stores the cookies
 * The CCRS system appears to have a 15 minute timeout.
 *
 * SPDX-License-Identifier: MIT
 */

require_once(__DIR__ . '/../boot.php');

// Check
$cookie_file = sprintf('%s/var/ccrs-cookies.json', APP_ROOT);
if (is_file($cookie_file)) {

	$age = time() - filemtime($cookie_file);
	if ($age < 600) {
		echo "AUTH: $age s old\n";
	}

	exit(0);

}

// Get
$u = \OpenTHC\Config::get('cre/usa/wa/ccrs/username');
$p = \OpenTHC\Config::get('cre/usa/wa/ccrs/password');

$cookie_data = [];

try {
	$cre = new \OpenTHC\CRE\CCRS([]);
	$cookie_data = $cre->auth($u, $p);
} catch (\Exception $e) {
	echo "FAIL: ";
	echo $e->getMessage();
	echo "\n";
	exit(1);
}

// Save
$d = json_encode($cookie_data, JSON_PRETTY_PRINT);
$x = file_put_contents($cookie_file, $d);
if (false === $x) {
	echo "Error writing to: $cookie_file\n";
	exit(1);
}

exit(0);
