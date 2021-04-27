#!/usr/bin/php
<?php
/**
 * Sync a License & Data to the Database
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$opt = getopt('', [
	'config:',
]);
$cfg_file = $opt['config'];
if (empty($cfg_file)) {
	echo "You Must supply a --config=<file>\n";
	exit(1);
}
$cfg_file = realpath($cfg_file);
if (empty($cfg_file)) {
	echo "You Must supply a valid --config=<file>\n";
	exit(1);
}
$cfg_data = file_get_contents($cfg_file);
$_SESSION = json_decode($cfg_data, true);
if (empty($_SESSION)) {
	echo "You Must supply a valid --config=<file>, JSON parsing failed\n";
	exit(1);
}

$mdb = new \OpenTHC\Bong\Middleware\Database(null);
$dbc = $mdb->connect();

_set_option($dbc, 'sync-time-alpha', json_encode(date(\DateTime::RFC3339)));

// Switch to Sub-Script
switch ($_SESSION['cre']['engine']) {
	case 'biotrack':
		require_once(__DIR__ . '/sync-biotrack.php');
	break;
	case 'leafdata':
		require_once(__DIR__ . '/sync-leafdata.php');
	break;
	case 'metrc':
		require_once(__DIR__ . '/sync-metrc.php');
	break;
}

_set_option($dbc, 'sync-time-omega', json_encode(date(\DateTime::RFC3339)));


/**
 * Table Name Mapper
 */
function _tab_name_map($obj): string
{
	$ret = null;
	switch ($obj) {
		case 'batch':
		case 'contact':
		case 'disposal':
		case 'lab_result':
		case 'license':
		case 'lot':
		case 'lot_delta':
		case 'product':
		case 'section':
		case 'uom':
		case 'variety':
			$ret = $obj;
			break;
		case 'b2b':
		case 'b2b_sale':
			$ret = 'b2b_sale';
			break;
		case 'b2c':
		case 'b2c_sale':
			$ret = 'b2c_sale';
			break;
		case 'crop':
		case 'plant':
			$ret = 'crop';
			break;
		case 'harvest':
			$ret = 'crop_collect';
			break;
		case 'lab-result':
		case 'lab_result':
			$ret = 'lab_result';
			break;
		case 'plantbatch':
			$ret = 'batch';
			break;
		default:
			throw new \Exception(sprintf('Object Table "%s" Not Handled', $obj));
	}

	return $ret;

}
