#!/usr/bin/php
<?php
/**
 * Wrapper for the Upload Scripts
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$doc = <<<DOC
BONG CRE CCRS Upload Tool
Usage:
	cre-ccrs-upload --license=LICENSE_ID [--object=<OBJECT>] [--object-id=<OBJECT_ID>] [--force]

Options:
	--license=ID        The license ID of the one to work on.
	--object=TYPE...    The type of record to work on. [default: section,variety,product,crop,crop-collect,inventory,inventory-adjust,b2b-incoming,b2b-outgoing]
	--object-id=ID      To UPLOAD only a single item.
	--force
DOC;

$res = Docopt::handle($doc);
$cli_args = $res->args;
// print_r($cli_args);

// Lock
$key = implode('/', [ __FILE__, $cli_args['--license'] ]);
$lock = new \OpenTHC\CLI\Lock($key);
if ( ! $lock->create()) {
	syslog(LOG_DEBUG, sprintf('LOCK: "%s" Failed', $key));
	return 0;
}

$dbc = _dbc();
$License = _load_license($dbc, $cli_args['--license']);

// Action
$obj_list = explode(',', $cli_args['--object']);

// Check Parameters
foreach ($obj_list as $obj) {
	if ( ! preg_match('/^(section|variety|product|crop|crop\-collect|crop\-finish|inventory|inventory\-adjust|b2b\-incoming|b2b\-outgoing|b2b\-outgoing\-manifest)$/', $obj)) {
		echo "Cannot Match Object [CCU-058]\n";
		exit(1);
	}
}

// Run the Scripts
foreach ($obj_list as $obj) {

	switch ($obj) {
		case 'crop':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Crop\Export($License);
			$csv->create($cli_args['--force']);
			break;
		case 'crop-collect':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Crop\Collect\Export($License);
			$csv->create($cli_args['--force']);
			break;
		case 'inventory':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Inventory\Export($License);
			$csv->create($cli_args['--force']);
			break;
		case 'inventory-adjust':
			// $csv = new \OpenTHC\Bong\CRE\CCRS\Inventory\Adjust\Export($License);
			// $csv->create($cli_args['--force']);
			break;
		case 'product':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Product\Export($License);
			$csv->create($cli_args['--force']);
			break;
		case 'section':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Section\Export($License);
			$csv->create($cli_args['--force']);
			break;
		case 'variety':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Variety\Export($License);
			$csv->create($cli_args['--force']);
			break;
		default:

			$obj_file = sprintf('%s/cre-ccrs-upload-%s.php', __DIR__, $obj);
			$obj_func = sprintf('_cre_ccrs_upload_%s', str_replace('-', '_', $obj));

			require_once($obj_file);

			// Improve Args?
			$res = call_user_func($obj_func, $cli_args);
			// Always Null
			// var_dump($res);
	}
}
