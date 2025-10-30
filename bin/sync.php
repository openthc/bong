#!/usr/bin/php
<?php
/**
 * Sync a License & Data to the Database
 *
 * SPDX-License-Identifier: MIT
 */

use OpenTHC\Bong\CRE;

require_once(__DIR__ . '/../boot.php');

$doc = <<<DOC
BONG CRE Sync
Usage:
	sync <command> [<command-options>...]

Commands:
	auth                  Authenticate to CRE
	ping                  *auth
	license-status        Show License Status
	license-verify        Re-Init a License and try to Verify via magic Section
	review                Review Data 400 Level Errors

Options:
	--company=<ID>
	--license=<LICENSE_ID_LIST>
	--object=<OBJECT_NAME_LIST>
DOC;

$res = Docopt::handle($doc, [
	'exit' => false,
	'help' => true,
	'optionsFirst' => true,
]);
$cli_args = $res->args;
// var_dump($cli_args);
switch ($cli_args['<command>']) {
case 'auth':
case 'ping':

	$doc = <<<DOC
	BONG CRE Sync Auth/Ping
	Usage:
		auth --company=<ID> --cre=<CRE> --cre-auth=<AUTH>
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args['<command-options>'],
		'exit' => true,
		'help' => true,
		'optionsFirst' => false,
	]);
	$cli_args = $res->args;
	// var_dump($cli_args);

	$dbc = _dbc();
	$Company = $dbc->fetchRow('SELECT * FROM company WHERE id = :cp0', [
		':cp0' => $cli_args['--company']
	]);
	if (empty($Company['id'])) {
		throw new \Exception('Company Not Found', 404);
	}

	$cfg = \OpenTHC\CRE::getConfig($cli_args['--cre']);
	$cfg['license-sk'] = $cli_args['--cre-auth'];
	$cre = \OpenTHC\CRE::factory($cfg);

	$res = $cre->license()->search();
	foreach ($res['data'] as $License0) {
		// FacilityId
		echo "Found License: {$License0['Name']}\n";
		$chk = $dbc->fetchRow('SELECT * FROM license WHERE company_id = :cp0 AND code = :l0', [
			':cp0' => $Company['id'],
			':l0' => $License0['License']['Number'],
		]);
		if (empty($chk)) {
			$dbc->insert('license', [
				'id' => _ulid(),
				'company_id' => $Company['id'],
				'code' => $License0['License']['Number'],
				'name' => $License0['Name'],
			]);
		}
	}

	break;

case 'license-status':
case 'license-verify':
case 'review':
	throw new \Exception('Not Handled', 501);
	break;
case 'sync':
	_bong_sync($cli_args['<command-options>']);
	break;
}
// var_dump($cli_args);
exit;

// _set_option($dbc, 'sync-time-alpha', json_encode(date(\DateTime::RFC3339)));

// Switch to Sub-Script
switch ($_SESSION['cre']['engine']) {
	case 'biotrack':
		require_once(__DIR__ . '/sync-biotrack.php');
		break;
	case 'ccrs':
		require_once(__DIR__ . '/lib/Sync/CCRS.php');
		break;
	case 'metrc':
		require_once(__DIR__ . '/sync-metrc.php');
		break;
}

_set_option($dbc, 'sync-time-omega', json_encode(date(\DateTime::RFC3339)));

/**
 *
 */
function _bong_sync($argv)
{
	$doc = <<<DOC
	BONG CRE Sync
	Usage:
		sync --company=<ID>  --cre=<CRE> --cre-auth=<AUTH> [ --object=LIST ] [--date-alpha=A ] [ --date-omega=DT ]
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $argv,
		'exit' => true,
		'help' => true,
		'optionsFirst' => false,
	]);
	$cli_args = $res->args;
	// var_dump($cli_args);

	$dbc = _dbc();

	$Company = $dbc->fetchRow('SELECT * FROM company WHERE id = :cp0', [
		':cp0' => $cli_args['--company']
	]);
	if (empty($Company['id'])) {
		throw new \Exception('Company Not Found', 404);
	}
	$res_license = $dbc->fetchAll('SELECT * FROM license WHERE company_id = :cp0', [
		':cp0' => $Company['id'],
	]);


	$cfg = \OpenTHC\CRE::getConfig($cli_args['--cre']);
	$cfg['license-sk'] = $cli_args['--cre-auth'];
	$cre = \OpenTHC\CRE::factory($cfg);

	// $req = $cre->_curl_init('/unitsofmeasure/v2/active');
	// $res = $cre->_curl_exec($req);
	// var_dump($res);

	// $req = $cre->_curl_init('/wastemethods/v2/');
	// $res = $cre->_curl_exec($req);
	// var_dump($res);

	// Filter from CLI Args?
	$res = $cre->license()->search();
	// print_r($cre_license_list);
	foreach ($res['data'] as $x) {
		echo $x['License']['Number'];
		echo ' ';
		echo $x['Name'];
		echo "\n";
	}

	foreach ($res_license as $License) {

		$License['guid'] = $License['code'];
		$cre->setLicense($License);

		$cre_sync = new \OpenTHC\Bong\CRE\Metrc2023\Sync($cre, $dbc);
		$res = $cre_sync->execute();
		var_dump($res);

	}

	exit(0);

	// $obj_list = [];
	// $obj_list[] = 'license'; // /facilities/v2/
	// $obj_lsit[] = 'harvests';
	// // $obj_list[] = 'items';
	// $obj_list[] = 'locations';
	// $obj_list[] = 'plant';
	// $obj_list[] = 'uom'; // '/unitsofmeasure/v2/active';
	// foreach ($obj_list as $obj_stub) { // } => $obj_name) {
	// 	if ( ! $sync->is_time_aware($obj_stub)) {
	// 		echo "SYNC: $obj_stub\n";
	// 		// _sync_object_data($dbc, $cre, $obj_stub);
	// 	}
	// }

	// For the Dates
	// $d0 = new DateTime();
	// $d1 = new DateTime();
	// if (!empty($opt['from'])) {
	// 	$d0 = new DateTime($opt['from']);
	// } else {
	// 	$d0->sub(new DateInterval('P7D')); // A Week
	// }
	// if (!empty($opt['thru'])) {
	// 	$d1 = new DateTime($opt['thru']);
	// }

	// $dL = clone $d0;
	// while ($dL < $d1) {

	// 	$st0 = $dL->format(DateTime::RFC3339);
	// 	$dL->add(new DateInterval('PT24H'));
	// 	$st1 = $dL->format(DateTime::RFC3339);

	// 	// Set Date to View
	// 	$sync->setTimeAlpha($st0);
	// 	$sync->setTimeOmega($st1);

	// 	foreach ($obj_list as $obj_stub => $obj_name) {
	// 		echo "Sync: {$License['name']} / {$obj_stub} {$st0}\n";
	// 		// _sync_object_data($dbc, $cre, $obj_stub);
	// 	}
	// }

	// $Company['cre'];
	// $cfg = \OpenTHC\CRE::getConfig('usa-mi');


}

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
		case 'inventory':
		case 'inventory_adjust':
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
