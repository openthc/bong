<?php
/**
 * Evaluate the Data and Attempt Repairs
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = _dbc();

$obj_want_list = [ 'variety', 'section', 'product', 'crop', 'inventory', 'b2b-outgoing', 'b2b-incoming' ];
if ( ! empty($cli_args['--object'])) {
	$obj_want_list = explode(',', $cli_args['--object']);
}

// If License then Filter, Else Any/All
$license_list = _load_license_list($dbc, $cli_args);
foreach ($license_list as $License) {

	echo "# License: {$License['id']} / {$License['code']} / {$License['name']}\n";

	$obj_eval_list = [ 'variety', 'section', 'product', 'crop', 'inventory' ];
	foreach ($obj_eval_list as $obj) {
		if (in_array($obj, $obj_want_list)) {
			_eval_object($dbc, $License, $obj);
		}
	}

	// _eval_object($dbc, $License, 'crop_finish');
	// _eval_object($dbc, $License, 'inventory_adjust');

	if (in_array('b2b-outgoing', $obj_want_list)) {
		_eval_b2b_outgoing($dbc, $License);
	}
	if (in_array('b2b-outgoing-file', $obj_want_list)) {
		_eval_b2b_outgoing_file($dbc, $License);
	}

	if (in_array('b2b-incoming', $obj_want_list)) {
		_eval_b2b_incoming($dbc, $License);
	}
}


/**
 *
 */
function _eval_object($dbc, $License, string $obj)
{
	$sql = <<<SQL
	SELECT id, data
	FROM $obj
	WHERE license_id = :l0
		AND stat = 400
		-- Filter to just find specific errors
		-- AND data::text LIKE '%Invalid Area%'
		-- AND data::text LIKE '%Invalid Product%'
		-- AND data::text ILIKE '%["Strain is required", "Area is required", "Product is required", "Invalid Area", "Invalid Product"]%'
		-- AND data::text LIKE '%["Invalid Area", "Invalid Product"]%'
		-- AND data::text LIKE '%Strain Name reported is not linked to the license number%'
	ORDER BY license_id, id
	SQL;

	$res = $dbc->fetchAll($sql, [
		':l0' => $License['id'],
	]);

	foreach ($res as $rec) {

		$rec['data'] = json_decode($rec['data'], true);

		$err = $rec['data']['@result']['data'][0];
		if (empty($err)) {
			continue;
		}

		switch ($err) {
			case 'Integrator is not authorized to update licensee':

				$sql = sprintf('UPDATE %s SET stat = 403 WHERE license_id = :l0 AND id = :o0', $obj);
				$dbc->query($sql, [
					':l0' => $License['id'],
					':o0' => $rec['id']
				]);
				$dbc->query('UPDATE license SET stat = 403 WHERE id = :l0 AND stat != 403', [
					':l0' => $rec['license_id']
				]);

				continue 2; // foreach

				break;

			case 'LicenseNumber is required':

				$cmd = _sync_command($dbc, $License, 'section', $rec['data']['@source']['section']['id'], $err);
				echo "$cmd\n";

				continue 2; // foreach

				break;

			case 'Invalid Area':

				echo "## SECTION: {$rec['data']['@source']['section']['id']} = {$rec['data']['@source']['section']['name']}\n";
				echo "## SELECT id, stat FROM section WHERE license_id = '{$License['id']}' AND id = '{$rec['data']['@source']['section']['id']}';\n";

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['section']['name'], 100);
				echo "## SELECT id FROM log_upload WHERE name LIKE 'SECTION UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = _sync_command($dbc, $License, 'section', $rec['data']['@source']['section']['id'], $err);
				echo "$cmd\n";

				break;

			case 'Invalid Product':

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['product']['name'], 100);

				echo "## PRODUCT: {$rec['data']['@source']['product']['id']} = {$rec['data']['@source']['product']['name']}\n";
				echo "## SELECT id, stat FROM product WHERE license_id = '{$License['id']}' AND id = '{$rec['data']['@source']['product']['id']}';\n";
				echo "## SELECT id FROM log_upload WHERE name LIKE 'PRODUCT UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = _sync_command($dbc, $License, 'product', $rec['data']['@source']['product']['id'], $err);
				echo "$cmd\n";

				break;

			case 'Strain Name reported is not linked to the license number. Please ensure the strain being reported belongs to the licensee':

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['variety']['name'], 100);
				echo "## VARIETY: {$rec['data']['@source']['variety']['id']} = {$rec['data']['@source']['variety']['name']}\n";
				echo "## SELECT id, stat FROM variety WHERE license_id = '{$License['id']}' AND name = '$n';\n";
				echo "## SELECT id FROM log_upload WHERE name LIKE 'VARIETY UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = _sync_command($dbc, $License, 'variety', $rec['data']['@source']['variety']['id'], $err);
				echo "$cmd\n";

				break;

			case 'ExternalIdentifier not found':
			case 'HarvestDate must be a date':
			case 'If Useable Cannabis is selected Unit Weight Gram cannot be Zero':
			case 'Invalid OpenTHC Product Type':
			case 'QuantityOnHand is greater than InitialQuantity':
			case 'Total Cost cannot equal zero':
				// Known errors w/no special case handling, just re-sync
				break;
			default:

				print_r($rec);

				echo "!! NOT HANDLED: $err\n";
				exit(1);

				break;

		}

		$cmd = _sync_command($dbc, $License, $obj, $rec['id'], $err);
		echo "$cmd\n";

		echo "\n";
	}

}

/**
 *
 */
function _eval_b2b_incoming($dbc, $License)
{
	$sql = <<<SQL
	SELECT b2b_incoming_item.id, b2b_incoming_item.b2b_incoming_id, b2b_incoming_item.data
	FROM b2b_incoming
	JOIN b2b_incoming_item ON b2b_incoming.id = b2b_incoming_item.b2b_incoming_id
	WHERE b2b_incoming.target_license_id = :l0
	  AND b2b_incoming.created_at >= '2025-01-01'
	--   AND b2b_incoming.created_at >= '2021-01-01' AND b2b_incoming.created_at < '2023-01-01'
	  AND b2b_incoming_item.stat IN (400, 404)
	ORDER BY b2b_incoming.target_license_id, b2b_incoming_item.id
	SQL;

	$res = $dbc->fetchAll($sql, [
		':l0' => $License['id'],
	]);

	foreach ($res as $rec) {

		$rec['data'] = json_decode($rec['data'], true);

		$err_list = $rec['data']['@result']['data'][0];

		while ($err = array_shift($err_list)) {
			switch ($err) {
				case 'ExternalIdentifier not found':
					// Is this My(TARGET) Inventory Not Found?
					// Or is this the SOURCE Inventory Not Found
					$dbc->query('UPDATE b2b_incoming_item SET stat = 100 WHERE id = :bii0', [
						':bii0' => $rec['id']
					]);
					$dbc->query('UPDATE b2b_incoming SET stat = 100 WHERE stat != 100 AND id = :bi0 AND target_license_id = :l0', [
						':l0' => $License['id'],
						':bi0' => $rec['b2b_incoming_id'],
					]);
					break;
				case 'FromInventoryExternalIdentifier is required':
				case 'FromLicenseNumber is required':
				case 'FromLicenseNumber must be numeric':
				case 'Invalid From LicenseNumber':
				case 'ToLicenseNumber is required':
					$cmd = _sync_command($dbc, $License, 'b2b-incoming', $rec['b2b_incoming_id'], $err);
					echo "$cmd\n";
					// echo "^^^ Inspect Error: '$err'\n";
					break;
				case 'Invalid ToInventoryExternalIdentifier':
				case 'ToInventoryExternalIdentifier is required':

					$inv_guid = $rec['data']['@source']['target_inventory']['id'] ?: $rec['data']['@source']['target_lot']['id'] ?: $rec['data']['@source']['source_lot']['id'];
					if (empty($inv_guid)) {
						print_r($rec);
						echo "\n^^^^^ INVALID RECORD TYPE?\n";
						exit;
					}

					$cmd = _sync_command($dbc, $License, 'inventory', $inv_guid, $err);
					echo "$cmd\n";
					$cmd = _sync_command($dbc, $License, 'b2b-incoming', $rec['b2b_incoming_id'], $err);
					echo "$cmd\n";

					break;

				case 'ToLicense cannot be the same license number as FromLicense':
				case 'No Negative Entries Allowed':
				case '':
					// Ignore, Cannot Fix
					break;
				default:
					var_dump($rec);
					echo "FAIL: _eval_b2b_incoming UNHANDLED '{$err}'\n";
					exit(1);
					break;
			}
		}
	}

}

/**
 *
 */
function _eval_b2b_outgoing($dbc, $License)
{
	$sql = <<<SQL
	SELECT b2b_outgoing_item.id, b2b_outgoing_item.b2b_outgoing_id, b2b_outgoing_item.data
	FROM b2b_outgoing
	JOIN b2b_outgoing_item ON b2b_outgoing.id = b2b_outgoing_item.b2b_outgoing_id
	WHERE b2b_outgoing.source_license_id = :l0
	  AND b2b_outgoing.created_at >= '2025-01-01'
	--   AND (b2b_outgoing.stat IN (400, 404)
	  AND b2b_outgoing_item.stat IN (400, 404)
	ORDER BY b2b_outgoing.source_license_id, b2b_outgoing_item.id
	SQL;

	$res = $dbc->fetchAll($sql, [
		':l0' => $License['id'],
	]);

	foreach ($res as $rec) {

		$rec['data'] = json_decode($rec['data'], true);

		// I don't think this one is used any more
		$err = $rec['data']['@result']['data'][0];
		if (empty($err)) {
			// And it always has to pick this one
			$err = $rec['data']['@result']['ErrorMessage'];
		}

		switch ($err) {
			case 'Invalid InventoryExternalIdentifier':

				// echo "## B2B OUTGOING ITEM\n";

				$dbc->query('UPDATE b2b_outgoing_item SET stat = 100 WHERE id = :bii0 AND stat != 100', [
					':bii0' => $rec['id']
				]);
				// $dbc->query('UPDATE b2b_outgoing SET stat = 100 WHERE stat != 100 AND id = :bi0 AND source_license_id = :l0', [
				// 	':l0' => $License['id'],
				// 	':bi0' => $rec['b2b_outgoing_id'],
				// ]);

				// Upload Inventory
				$oid = $rec['data']['@source']['inventory']['id'] ?: $rec['data']['@source']['lot']['id'];
				$cmd = _sync_command($dbc, $License, 'inventory', $oid, $err);
				echo "$cmd\n";

				$cmd = _sync_command($dbc, $License, 'b2b-outgoing', $rec['b2b_outgoing_id'], $err);
				echo "$cmd\n";

				break;

			case 'Cannabis Excise Tax does not equal 37% of Unit Price':
			case 'Invalid SaleDetail':
			case 'InventoryExternalIdentifier or PlantExternalIdentifier is required':
			case 'Only Medical Sales Excise tax can be 0':
			case 'Quantity is required':
			case 'SaleDetailExternalIdentifier not found':
			case 'SaleExternalIdentifier not found':
			case 'Sales cannot be self-reported':

				$cmd = _sync_command($dbc, $License, 'b2b-outgoing', $rec['b2b_outgoing_id'], $err);
				echo "$cmd\n";

				break;

			default:
				var_dump($rec);
				echo "FAIL: _eval_b2b_outgoing UNHANDLED '{$err}'\n";
				exit(1);
				// var_dump($rec);
				// $cmd = _sync_command($License, 'b2b-outgoing', $rec['b2b_outgoing_id'], $err);
				// echo "$cmd\n";
				// exit;
				// $dbc->query('UPDATE b2b_outgoing_item SET stat = 100 WHERE id = :bii0 AND stat != 100', [
				// 	':bii0' => $rec['id']
				// ]);
				// $dbc->query('UPDATE b2b_outgoing SET stat = 100 WHERE stat != 100 AND id = :bi0 AND source_license_id = :l0', [
				// 	':l0' => $License['id'],
				// 	':bi0' => $rec['b2b_outgoing_id'],
				// ]);
				break;
		}

	}
}

function _eval_b2b_outgoing_file($dbc, $License)
{
	// $doc = <<<DOC
	// BONG CRE CCRS Upload Script Creator

	// Create a shell script to upload data for each license

	// Usage:
	// 	upload-b2b-outgoing-resend [--license=<LIST>] [--object-id=<LIST>] [--force]

	// Options:
	// 	--license=<LID>
	// 	--object-id=<OID>
	// 	--force
	// DOC;

	// $res = Docopt::handle($doc, [
	// 	'argv' => $cli_args,
	// ]);
	// $cli_args = $res->args;

	// $dbc = _dbc();

	$cfg = \OpenTHC\CRE::getConfig('usa-wa');
	$cre_service_key = $cfg['service-sk'];

	$arg = [];
	$sql = <<<SQL
	SELECT id, source_license_id
	FROM b2b_outgoing
	WHERE id NOT IN (
		SELECT id
		FROM b2b_outgoing_file
	)
	AND b2b_outgoing.created_at >= '2025-01-01'
	ORDER BY id
	OFFSET 300
	LIMIT 100
	SQL;

	// if ( ! empty($cli_args['--object-id'])) {
	// 	$arg = [];
	// 	$arg[':oid'] = $cli_args['--object-id'];
	// 	$sql = <<<SQL
	// 	SELECT id, source_license_id
	// 	FROM b2b_outgoing
	// 	WHERE id = :oid
	// 	SQL;
	// }

	$res = $dbc->fetchAll($sql, $arg);
	foreach ($res as $rec) {

		echo "B2B MANIFEST: {$rec['id']}\n";

		$b2b = $dbc->fetchRow('SELECT * FROM b2b_outgoing WHERE source_license_id = :l0 AND id = :b1', [
			':b1' => $rec['id'],
			':l0' => $rec['source_license_id'],
		]);

		$b2b['data'] = json_decode($b2b['data'], true);

		$req_ulid = _ulid();
		$req_code = sprintf('B2B_MANIFEST UPLOAD %s', $req_ulid);

		$b2b_helper = new \OpenTHC\Bong\CRE\CCRS\B2B();
		$csv_name = sprintf('Manifest_%s_%s.csv', $cre_service_key, $req_ulid);
		$csv_temp = $b2b_helper->create_outgoing_csv($b2b['data']['@source'], $req_ulid);

		$csv_blob = stream_get_contents($csv_temp);
		echo ">>> CSV >>>\n$csv_blob\n###\n";
		exit;

		$rec = [];
		$rec['id'] = $req_ulid;
		$rec['license_id'] = $b2b['source_license_id'];
		$rec['name'] = $req_code;
		$rec['source_data'] = json_encode([
			'name' => $csv_name,
			'data' => $csv_blob
		]);
		$dbc->insert('log_upload', $rec);

	}

}

/**
 *
 */
function _load_license_list($dbc, $cli_args)
{
	$ret = [];
	if ( ! empty($cli_args['--license'])) {
		$ret = $dbc->fetchAll('SELECT * FROM license WHERE id = :l0', [
			':l0' => $cli_args['--license'],
		]);
	} else {
		$ret = $dbc->fetchAll('SELECT * FROM license WHERE stat IN (100, 200) ORDER BY id');
	}

	return $ret;
}

/**
 * Sync Command Helper
 */
function _sync_command($dbc, $License, string $obj, string $oid, $err) {

	$cmd = [];
	$cmd[] = '/opt/openthc/app/bin/sync.php';
	$cmd[] = sprintf('--company=%s', $License['company_id']);
	$cmd[] = sprintf('--license=%s', $License['id']);
	$cmd[] = sprintf('--object=%s', escapeshellarg($obj));
	$cmd[] = sprintf('--object-id=%s', escapeshellarg($oid));
	$cmd[] = '--force';
	$cmd = implode(' ', $cmd);

	$dbc->insert('log_upload_review', [
		'id' => _ulid(),
		'license_id' => $License['id'],
		'ulid' => $oid,
		'type' => $obj,
		'note' => $err,
	]);

	return $cmd;

}
