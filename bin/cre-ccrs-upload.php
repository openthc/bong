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
	--object=TYPE...    The type of record to work on. [default: section,variety,product,crop,inventory,inventory-adjust,b2b-incoming,b2b-outgoing]
	--object-id=ID      To UPLOAD only a single item.
	--force
DOC;

$res = Docopt::handle($doc);
$cli_args = $res->args;

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
	if ( ! preg_match('/^(section|variety|product|crop|crop\-finish|inventory|inventory\-adjust|b2b\-incoming|b2b\-outgoing|b2b\-outgoing\-manifest)$/', $obj)) {
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
		case 'inventory':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Inventory\Export($License);
			$csv->create($cli_args['--force']);
			break;
		case 'inventory-adjust':
			$csv = new \OpenTHC\Bong\CRE\CCRS\Inventory\Adjust\Export($License);
			$csv->create($cli_args['--force']);
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
			// $csv->setForce($cli_args['--force']);
			$csv->create($cli_args['--force']);
			break;
		default:

			$obj_file = sprintf('%s/cre-ccrs-upload-%s.php', __DIR__, $obj);
			require_once($obj_file);

			$obj = str_replace('-', '_', $obj);
			$obj_func = sprintf('_cre_ccrs_upload_%s', $obj);

			// Improve Args?
			$res = call_user_func($obj_func, $cli_args);
	}
}


/**
 * Utility Functions
 */
function _load_license($dbc, $license_id, $object_table=null)
{
	$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $license_id ]);
	if (empty($License['id'])) {
		echo "Invalid License '{$license_id}' [CCU-071]\n";
		exit(1);
	}
	switch ($License['stat']) {
		case 100:
		case 102:
		case 200:
			// OK
			break;
		case 403:
		case 500:
		case 666:
			// $dbc->query("UPDATE {$object_table} SET stat = :s1 WHERE license_id = :l0 AND stat != :s1", [
			// 	':l0' => $license_id,
			// 	':s1' => $License['stat']
			// ]);
			// Pass Thru
		default:
			echo "Invalid License:'$license_id' status:'{$License['stat']}'\n";
			exit(1);
	}

	return $License;

}
