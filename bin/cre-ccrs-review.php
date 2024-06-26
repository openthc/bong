<?php
/**
 * Evaluate the Data and Attempt Repairs
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = _dbc();

// If License then Filter, Else Any/All
$license_list = _load_license_list($dbc, $cli_args);
foreach ($license_list as $License) {

	echo "# License: {$License['id']} / {$License['code']} / {$License['name']}\n";

	_eval_object($dbc, $License, 'section');
	_eval_object($dbc, $License, 'variety');
	_eval_object($dbc, $License, 'product');
	_eval_object($dbc, $License, 'crop');
	// _eval_object($dbc, $License, 'crop_finish');
	_eval_object($dbc, $License, 'inventory');
	// _eval_object($dbc, $License, 'inventory_adjust');
	_eval_b2b_outgoing($dbc, $License);
	_eval_b2b_incoming($dbc, $License);
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

			case 'Invalid Area':

				echo "## SECTION: {$rec['data']['@source']['section']['id']} = {$rec['data']['@source']['section']['name']}\n";
				echo "## SELECT id, stat FROM section WHERE license_id = '{$License['id']}' AND id = '{$rec['data']['@source']['section']['id']}';\n";

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['section']['name'], 100);
				echo "## SELECT id FROM log_upload WHERE name LIKE 'SECTION UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = _sync_command($License, 'section', $rec['data']['@source']['section']['id']);
				echo "$cmd\n";

				break;

			case 'Invalid Product':

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['product']['name'], 100);

				echo "## PRODUCT: {$rec['data']['@source']['product']['id']} = {$rec['data']['@source']['product']['name']}\n";
				echo "## SELECT id, stat FROM product WHERE license_id = '{$License['id']}' AND id = '{$rec['data']['@source']['product']['id']}';\n";
				echo "## SELECT id FROM log_upload WHERE name LIKE 'PRODUCT UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = _sync_command($License, 'product', $rec['data']['@source']['product']['id']);
				echo "$cmd\n";

				break;

			case 'Strain Name reported is not linked to the license number. Please ensure the strain being reported belongs to the licensee':

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['variety']['name'], 100);
				echo "## VARIETY: {$rec['data']['@source']['variety']['id']} = {$rec['data']['@source']['variety']['name']}\n";
				echo "## SELECT id, stat FROM variety WHERE license_id = '{$License['id']}' AND name = '$n';\n";
				echo "## SELECT id FROM log_upload WHERE name LIKE 'VARIETY UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = _sync_command($License, 'variety', $rec['data']['@source']['variety']['id']);
				echo "$cmd\n";

				break;

			default:

				echo "## NOT HANDLED: $err\n";

				break;

		}

		$cmd = _sync_command($License, $obj, $rec['id']);
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
	  AND b2b_incoming_item.stat IN (400, 404)
	ORDER BY b2b_incoming.target_license_id, b2b_incoming_item.id
	SQL;

	$res = $dbc->fetchAll($sql, [
		':l0' => $License['id'],
	]);

	foreach ($res as $rec) {

		$rec['data'] = json_decode($rec['data'], true);

		$err = $rec['data']['@result']['data'][0];
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
			case 'ToLicenseNumber is required':
				$cmd = _sync_command($License, 'b2b-incoming', $rec['b2b_incoming_id']);
				echo "$cmd\n";
				break;
			default:
				var_dump($rec);
				echo "FAIL: _eval_b2b_incoming UNHANDLED '{$err}'\n";
				exit;
				break;
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
	  AND b2b_outgoing_item.stat IN (400, 404)
	ORDER BY b2b_outgoing.source_license_id, b2b_outgoing_item.id
	SQL;

	$res = $dbc->fetchAll($sql, [
		':l0' => $License['id'],
	]);

	foreach ($res as $rec) {

		$rec['data'] = json_decode($rec['data'], true);

		$err = $rec['data']['@result']['data'][0];
		if (empty($err)) {
			$err = $rec['data']['@result']['ErrorMessage'];
			// var_dump($rec); exit;
		}
		switch ($err) {
			case 'Invalid InventoryExternalIdentifier':
				echo "## B2B OUTGOING ITEM\n";

				$dbc->query('UPDATE b2b_outgoing_item SET stat = 100 WHERE id = :bii0 AND stat != 100', [
					':bii0' => $rec['id']
				]);
				// $dbc->query('UPDATE b2b_outgoing SET stat = 100 WHERE stat != 100 AND id = :bi0 AND source_license_id = :l0', [
				// 	':l0' => $License['id'],
				// 	':bi0' => $rec['b2b_outgoing_id'],
				// ]);

				// Upload Inventory
				$oid = $rec['data']['@source']['inventory']['id'] ?: $rec['data']['@source']['lot']['id'];
				$cmd = _sync_command($License, 'inventory', $oid);
				echo "$cmd\n";

				break;

			case 'Invalid SaleDetail':
			case 'InventoryExternalIdentifier or PlantExternalIdentifier is required':
			case 'SaleExternalIdentifier not found':
				$cmd = _sync_command($License, 'b2b-outgoing', $rec['b2b_outgoing_id']);
				echo "$cmd\n";
				break;
			default:
				var_dump($rec);
				echo "FAIL: _eval_b2b_outgoing UNHANDLED '{$err}'\n";
				exit;
				$dbc->query('UPDATE b2b_outgoing_item SET stat = 100 WHERE id = :bii0 AND stat != 100', [
					':bii0' => $rec['id']
				]);
				// $dbc->query('UPDATE b2b_outgoing SET stat = 100 WHERE stat != 100 AND id = :bi0 AND source_license_id = :l0', [
				// 	':l0' => $License['id'],
				// 	':bi0' => $rec['b2b_outgoing_id'],
				// ]);
				break;
		}

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
function _sync_command($License, string $obj, string $oid) {

	$cmd = [];
	$cmd[] = '/opt/openthc/app/bin/sync.php';
	$cmd[] = sprintf('--company=%s', $License['company_id']);
	$cmd[] = sprintf('--license=%s', $License['id']);
	$cmd[] = sprintf('--object=%s', escapeshellarg($obj));
	$cmd[] = sprintf('--object-id=%s', escapeshellarg($oid)); // $rec['b2b_outgoing_id']);
	$cmd[] = '--force';
	$cmd = implode(' ', $cmd);

	return $cmd;

}
