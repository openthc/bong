#!/usr/bin/php
<?php
/**
 * This routine simply authenticates to the CCRS platform and stores the cookies
 * The CCRS system appears to have a 15 minute timeout.
 *
 * SPDX-License-Identifier: GPL-3.0-only
 */

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Communication\Message;

require_once(__DIR__ . '/../boot.php');

// Save the cookies in bong.sqlite
// $bf = new BrowserFactory();
$bf = new BrowserFactory('/usr/bin/chromium');
// $bf = new BrowserFactory('node_modules/puppeteer/.local-chromium/linux-686378/chrome-linux/chrome');
$b = $bf->createBrowser([
	// 'debugLogger' => 'php://stdout',
	'noSandbox' => true,
	// 'userDataDir' => sprintf('%s/chrome-profile', APP_ROOT)
]);

$page = $b->createPage();
$page->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36');

// Main Page
$page->navigate('https://cannabisreporting.lcb.wa.gov/')->waitForNavigation();
$url0 = $page->getCurrentUrl();
echo "url0:{$url0}\n";

// Needs Authentication?
if (preg_match('/^https:\/\/secureaccess\.wa\.gov\/FIM2\/sps\/auth/', $url0)) {

	// POST the Form?
	$code = sprintf('document.querySelector("#username").value = "%s";', \OpenTHC\Config::get('cre/usa/wa/ccrs/username'));
	$page->evaluate($code);

	// Password
	$code = sprintf('document.querySelector("#password").value = "%s";', \OpenTHC\Config::get('cre/usa/wa/ccrs/password'));
	$page->evaluate($code);

	// $page->screenshot()->saveToFile('ccrs0.png');
	// $page->evaluate('document.querySelector("#submit-button-row input").click()')->waitForPageReload();

	$page->mouse()->find('#submit-button-row input')->click();
	$page->waitForReload();

	$url1 = $page->getCurrentUrl();
	echo "url1:{$url1}\n";
	// $page->screenshot()->saveToFile('ccrs1.png');
} elseif (preg_match('/https:\/\/secureaccess\.wa\.gov\/FIM2\/sps\/sawidp\/saml20\/login/', $url0)) {
	// OK ? Only see this one intermittently
} elseif (preg_match('/^https:\/\/cannabisreporting\.lcb\.wa\.gov\//', $url0)) {
	// Authenticated
} else {
	echo "No Match: $url0\n";
}

// Save Cookies
$cookie_out = [];
$cookie_jar = $page->getAllCookies();
foreach ($cookie_jar as $c) {
	$a = (array)$c;
	$a = array_shift($a);
	$cookie_out[] = $a;
}

// Save
$f = sprintf('%s/var/ccrs-cookies.json', APP_ROOT);
$d = json_encode($cookie_out, JSON_PRETTY_PRINT);
$x = file_put_contents($f, $d);
if (false === $x) {
	echo "Error writing to: $f\n";
	exit(1);
}

exit(0);
