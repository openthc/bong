<?php
/**
 * Evaluate the Data and Attempt Repairs
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = _dbc();

$license_list = _load_license_list($dbc, $cli_args);
foreach ($license_list as $License) {
	echo "# License: {$License['id']}\n";
	// _eval_object($License, 'section');
	// _eval_object($License, 'variety');
	// _eval_object($License, 'product');
	// _eval_object($License, 'crop');
	// _eval_object($License, 'inventory');
	// _eval_b2b_incoming($License);
	_eval_b2b_outgoing($License);
}


/**
 *
 */
function _eval_object($License, string $obj)
{
	global $dbc;

	$sql = <<<SQL
	SELECT id, data
	FROM $obj
	WHERE license_id = :l0
	  AND stat = 400
	-- AND data::text LIKE '%["Invalid Area"]%'
	-- AND data::text ILIKE '%["Strain is required", "Area is required", "Product is required", "Invalid Area", "Invalid Product"]%'
	-- AND data::text LIKE '%["Invalid Area", "Invalid Product"]%'
	-- AND data::text LIKE '%["Invalid Product"]%'
	-- AND data::text LIKE '%Strain Name reported is not linked to the license number%'
	ORDER BY license_id, id
	SQL;

	$res = $dbc->fetchAll($sql, [
		':l0' => $License['id'],
	]);

	foreach ($res as $rec) {

		$rec['data'] = json_decode($rec['data'], true);

		$err = $rec['data']['@result']['data'][0];
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
				continue 2; // the foreach
				break;
			// case 'Invalid Area':
			case 'Invalid Product':

				echo "## PRODUCT: {$rec['data']['@source']['product']['id']} = {$rec['data']['@source']['product']['name']}\n";
				echo "## SELECT id, stat FROM product WHERE license_id = '{$License['id']}' AND id = '{$rec['data']['@source']['product']['id']}';\n";

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['product']['name'], 100);
				echo "## SELECT id FROM log_upload WHERE name LIKE 'PRODUCT UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = [];
				$cmd[] = '/opt/openthc/app/bin/sync.php';
				$cmd[] = sprintf('--company=%s', $License['company_id']);
				$cmd[] = sprintf('--license=%s', $License['id']);
				$cmd[] = sprintf('--object=%s', 'product');
				$cmd[] = sprintf('--object-id=%s', $rec['data']['@source']['product']['id']);
				$cmd = implode(' ', $cmd);
				echo "$cmd\n";

				break;

			case 'Strain Name reported is not linked to the license number. Please ensure the strain being reported belongs to the licensee':

				$n = \OpenTHC\CRE\CCRS::sanatize($rec['data']['@source']['variety']['name'], 100);
				echo "## VARIETY: {$rec['data']['@source']['variety']['id']} = {$rec['data']['@source']['variety']['name']}\n";
				echo "## SELECT id, stat FROM variety WHERE license_id = '{$License['id']}' AND name = '$n';\n";
				echo "## SELECT id FROM log_upload WHERE name LIKE 'VARIETY UPLOAD%' AND license_id = '{$License['id']}' AND source_data::text ILIKE '%$n%';\n";

				$cmd = [];
				$cmd[] = '/opt/openthc/app/bin/sync.php';
				$cmd[] = sprintf('--company=%s', $License['company_id']);
				$cmd[] = sprintf('--license=%s', $License['id']);
				$cmd[] = sprintf('--object=%s', 'variety');
				$cmd[] = sprintf('--object-id=%s', $rec['data']['@source']['variety']['id']);
				$cmd[] = '--force';
				$cmd = implode(' ', $cmd);
				echo "$cmd\n";

				break;

			default:
				echo "NOT HANDLED: $err\n";
				exit;
		}

		$cmd = [];
		$cmd[] = '/opt/openthc/app/bin/sync.php';
		$cmd[] = sprintf('--company=%s', $License['company_id']);
		$cmd[] = sprintf('--license=%s', $License['id']);
		$cmd[] = sprintf('--object=%s', $obj);
		$cmd[] = sprintf('--object-id=%s', $rec['id']);
		$cmd[] = '--force';
		$cmd = implode(' ', $cmd);
		echo "$cmd\n";

		echo "\n";
	}

}

/**
 *
 */
function _eval_b2b_incoming($License)
{
	global $dbc;

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
			default:
				echo "FAIL: UNHANDLED {$err}\n";
				break;
		}

	}

}

/**
 *
 */
function _eval_b2b_outgoing($License)
{
	global $dbc;

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
		switch ($err) {
			case 'Invalid InventoryExternalIdentifier':

				$dbc->query('UPDATE b2b_outgoing_item SET stat = 100 WHERE id = :bii0', [
					':bii0' => $rec['id']
				]);
				$dbc->query('UPDATE b2b_outgoing SET stat = 100 WHERE stat != 100 AND id = :bi0 AND source_license_id = :l0', [
					':l0' => $License['id'],
					':bi0' => $rec['b2b_outgoing_id'],
				]);

				// UPload Invenotry
				$cmd = [];
				$cmd[] = '/opt/openthc/app/bin/sync.php';
				$cmd[] = sprintf('--company=%s', $License['company_id']);
				$cmd[] = sprintf('--license=%s', $License['id']);
				$cmd[] = sprintf('--object=inventory');
				$cmd[] = sprintf('--object-id=%s', $rec['data']['@source']['inventory']['id'] ?: $rec['data']['@source']['lot']['id']);
				$cmd[] = '--force';
				$cmd = implode(' ', $cmd);
				echo "$cmd\n";
				break;
			case 'Invalid SaleDetail':
				break;
			case 'InventoryExternalIdentifier or PlantExternalIdentifier is required':
				break;
			default:
				echo "FAIL: UNHANDLED {$err}\n";
				$dbc->query('UPDATE b2b_outgoing_item SET stat = 100 WHERE id = :bii0', [
					':bii0' => $rec['id']
				]);
				$dbc->query('UPDATE b2b_outgoing SET stat = 100 WHERE stat != 100 AND id = :bi0 AND source_license_id = :l0', [
					':l0' => $License['id'],
					':bi0' => $rec['b2b_outgoing_id'],
				]);
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
