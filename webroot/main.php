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
// if ( ! empty($cfg['debug'])) {
// 	unset($con['errorHandler']);
// 	unset($con['phpErrorHandler']);
// }
// $con['response'] = function($c) {
// 	return new \OpenTHC\HTTP\Response();
// };
$con['notAllowedHandler'] = function($c) {
	return function ($REQ, $RES) {
		$RES = new \Slim\Http\Response(405);
		$RES = $RES->withProtocolVersion('1.1');
		return $RES->withJSON(array(
			'data' => [],
			'meta' => [
				'note' => 'HTTP Method Not Allowed',
			],
		));
	};
};
$con['response'] = function($c) {
	return new \OpenTHC\HTTP\Response();
};

// Authentication
$app->group('/auth', 'OpenTHC\Bong\Module\Auth')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');


// Browse Data
$app->map([ 'GET', 'POST' ], '/browse', 'OpenTHC\Bong\Controller\Browse')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');


// Core System Objects
$app->group('/company', 'OpenTHC\Bong\Module\Company')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

$app->group('/contact', 'OpenTHC\Bong\Module\Contact')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

$app->group('/license', 'OpenTHC\Bong\Module\License')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

// Core Company Specific Objects
$app->group('/product', 'OpenTHC\Bong\Module\Product')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

$app->group('/section', 'OpenTHC\Bong\Module\Section')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

$app->group('/variety', 'OpenTHC\Bong\Module\Variety')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

$app->group('/vehicle', 'OpenTHC\Bong\Module\Vehicle')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

// Batch
// $app->group('/batch', 'OpenTHC\Bong\Module\Batch')
// 	->add('OpenTHC\Bong\CRE')
// 	->add('OpenTHC\Bong\Middleware\Database')
// 	->add('OpenTHC\Middleware\Session');


// Crop - v1
$app->group('/crop', 'OpenTHC\Bong\Module\Crop')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

// Alias v0
$app->group('/plant', 'OpenTHC\Bong\Module\Crop')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

// Inventory - v1
$app->group('/inventory', 'OpenTHC\Bong\Module\Inventory')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

// InventoryAdjust - v1
$app->group('/inventory-adjust', 'OpenTHC\Bong\Module\InventoryAdjust')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');


// Lot - v0
$app->group('/lot', 'OpenTHC\Bong\Module\Inventory')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');


// Lab Result
$app->group('/lab', 'OpenTHC\Bong\Module\Lab')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');


// B2B
$app->group('/b2b', 'OpenTHC\Bong\Module\B2B')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');


// B2C
$app->group('/b2c', 'OpenTHC\Bong\Module\B2C')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\CRE')
	->add('OpenTHC\Bong\Middleware\Auth')
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
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

// Log Single
$app->map([ 'GET', 'POST' ], '/log/{id}', 'OpenTHC\Bong\Controller\Log:single')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

// Search
$app->get('/search', 'OpenTHC\Bong\Controller\Search')
	->add('OpenTHC\Bong\Middleware\Database')
	->add('OpenTHC\Bong\Middleware\Auth')
	->add('OpenTHC\Middleware\Session');

$app->get('/status', function($REQ, $RES, $ARG) {
	// Get the Currently Authenticated License Status
});

// Display System Info
$app->get('/system', 'OpenTHC\Bong\Controller\System')->add('OpenTHC\Middleware\Session');
$app->get('/system/ajax', 'OpenTHC\Bong\Controller\System:ajax')->add('OpenTHC\Middleware\Session');;
$app->get('/system/status', 'OpenTHC\Bong\Controller\System:status')->add('OpenTHC\Middleware\Session');;

// Return a list of supported CREs
// $app->get('/system/cre', function($REQ, $RES, $ARG) {

// 	$cre_list = \OpenTHC\Bong\CRE::getEngineList();

// 	return $RES->withJSON([
// 		'data' => $cre_list,
// 		'meta' => [],
// 	], 200, JSON_PRETTY_PRINT);

// });
$app->get('/system/ping', 'OpenTHC\Bong\Controller\System:ping');


// Data Uploads - CSV, Email, etc
$app->post('/upload', 'OpenTHC\Bong\Controller\Upload:outgoing');
$app->post('/upload/incoming', 'OpenTHC\Bong\Controller\Upload:incoming');
$app->post('/upload/outgoing', 'OpenTHC\Bong\Controller\Upload:outgoing');
$app->get('/upload/log', 'OpenTHC\Bong\Controller\Upload:log');


// Run the App
$app->run();
