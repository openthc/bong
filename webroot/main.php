<?php
/**
 * OpenTHC BONG Main Controller
 *
 * SPDX-License-Identifier: MIT
 */

require_once(dirname(dirname(__FILE__)) . '/boot.php');

// Slim Application
$cfg = [];
$cfg['debug'] = true;
$app = new \OpenTHC\App($cfg);

// Container Stuff
$con = $app->getContainer();
if (!empty($cfg['debug'])) {
	unset($con['errorHandler']);
	unset($con['phpErrorHandler']);
}
$con['response'] = function($c) {
	$r = new \OpenTHC\Bong\Response();
	return $r;
};


// Authentication
$app->group('/auth', 'OpenTHC\Bong\Module\Auth')
	->add('OpenTHC\Middleware\Session');


// Browse Data
$app->map([ 'GET', 'POST' ], '/browse', 'OpenTHC\Bong\Controller\Browse')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

// Types
$app->get('/license-type', 'OpenTHC\Bong\Controller\System:license_type')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

$app->get('/product-type', 'OpenTHC\Bong\Controller\System:product_type')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

// Core System Objects
$app->group('/company', 'OpenTHC\Bong\Module\Company')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

$app->group('/contact', 'OpenTHC\Bong\Module\Contact')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

$app->group('/license', 'OpenTHC\Bong\Module\License')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

// Core Company Specific Objects
$app->group('/product', 'OpenTHC\Bong\Module\Product')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

$app->group('/section', 'OpenTHC\Bong\Module\Section')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

$app->group('/variety', 'OpenTHC\Bong\Module\Variety')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

$app->group('/vehicle', 'OpenTHC\Bong\Module\Vehicle')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');

// $app->group('/data', 'OpenTHC\Bong\Module\Data')
// 	->add('OpenTHC\Bong\CRE')
// 	->add('OpenTHC\Bong\Middleware\Database')
// 	->add('OpenTHC\Middleware\Session');

// Batch
// $app->group('/batch', 'OpenTHC\Bong\Module\Batch')
// 	->add('OpenTHC\Bong\CRE')
// 	->add('OpenTHC\Bong\Middleware\Database')
// 	->add('OpenTHC\Middleware\Session');


// Crop
$app->group('/crop', 'OpenTHC\Bong\Module\Crop')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');


// Lot
$app->group('/lot', 'OpenTHC\Bong\Module\Lot')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');


// Lab Result
$app->group('/lab', 'OpenTHC\Bong\Module\Lab')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');


// B2B
$app->group('/b2b', 'OpenTHC\Bong\Module\B2B')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');


// B2C
$app->group('/b2c', 'OpenTHC\Bong\Module\B2C')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Middleware\Session');


// Waste Group
//$app->group('/disposal', 'App\Module\Disposal function() {
//
//	$this->get('', function($REQ, $RES, $ARG) {
//		return _from_cre_file('waste/search.php', $RES, $ARG);
//	});
//
//	$this->get('/{guid}', function($REQ, $RES, $ARG) {
//		return _from_cre_file('waste/single.php', $RES, $ARG);
//	});
//
//})
//->add('OpenTHC\Bong\Middleware\CRE')
//->add('OpenTHC\Bong\Middleware\Database')
//->add('OpenTHC\Middleware\Session');

// Log Access
$app->map([ 'GET', 'POST' ], '/log', 'OpenTHC\Bong\Controller\Log')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Middleware\Session');

// Display System Info
$app->get('/system', 'OpenTHC\Bong\Controller\System');

// Return a list of supported CREs
// $app->get('/system/cre', function($REQ, $RES, $ARG) {

// 	$cre_list = \OpenTHC\Bong\CRE::getEngineList();

// 	return $RES->withJSON([
// 		'data' => $cre_list,
// 		'meta' => [],
// 	], 200, JSON_PRETTY_PRINT);

// });
$app->get('/system/ping', 'OpenTHC\Bong\Controller\System:ping');


$app->post('/upload', 'OpenTHC\Bong\Controller\Upload');


// Custom Middleware?
$f = sprintf('%s/Custom/boot.php', APP_ROOT);
if (is_file($f)) {
	require_once($f);
}


// Run the App
$app->run();
