<?php
/**
 * Evaluate the License Status
 *
 *
 */
function _cre_ccrs_license_status($cli_args)
{
	$doc = <<<DOC
	BONG CRE CCRS License Status Update
	Usage:
		license-status [--license=LICENSE] [--update-stat]

	Options:
		--license=LICENSE  The License to Verify
		--update-stat      update the object status for the license
	DOC;

	$res = Docopt::handle($doc, [
		'argv' => $cli_args,
	]);
	$cli_args = $res->args;

	$dbc = _dbc();
	$rdb = \OpenTHC\Service\Redis::factory();

	// Full License List
	$license_list_full = [];

	// License List from PostgreSQL
	$res_license = $dbc->fetchAll('SELECT * FROM license');
	foreach ($res_license as $rec) {
		$rec['source'] = [ 'pgsql' ];
		$license_list_full[ $rec['id'] ] = $rec;
	}

	// License List from Redis
	$res_license = $rdb->keys('/license/*');
	foreach ($res_license as $key) {
		if (preg_match('/\/license\/(\w+)$/', $key, $m)) {

			$oid = $m[1];

			if (empty($license_list_full[ $oid ])) {
				$license_list_full[ $oid ] = [
					'id' => $oid,
					'source' => [ 'redis' ],
					'stat' => 100,
				];
			}
			$license_list_full[ $oid ]['source'][] = 'redis';

			// Could be NON ULID
			if ( ! preg_match('/^\w{26}$/', $oid)) {
				$chk = $dbc->fetchOne('SELECT id FROM license WHERE code = :c0', [ ':c0' => $oid ]);
				if ( ! empty($chk)) {
					$license_list_full[ $oid ]['goto'] = $chk;
					// Duplicate in REDIS
					echo "redis-cli del '$key' # really $chk\n";
				}
			}


		} else {
			echo "redis-cli del \"$key\"\n";
		}
	}

	// Only One License Request?
	if ( ! empty($cli_args['--license'])) {
		$license_list_full = array_filter($license_list_full, function($v) use ($cli_args) {
			return ($v['id'] == $cli_args['--license']);
		});
	}

	// $exo = \OpenTHC\BONG\CRE\CCRS\License\Status($dbc, $rdb);
	// $exo->execute();

	foreach ($license_list_full as $lic) {

		echo $lic['id'];
		echo "\t";
		echo implode('+', $lic['source']);
		echo "\t";

		// Remove Deprecated Legacy Items
		$rdb->hdel(sprintf('/license/%s', $lic['id']), '/push');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), '/push/time');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'b2b_incoming/sync');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'b2b/incoming/sync');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'crop/sync');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'inventory/sync');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'product/sync');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'section/sync');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'variety/sync');
		$rdb->hdel(sprintf('/license/%s', $lic['id']), 'variety/sync/time');
		// $rdb->hdel(sprintf('/license/%s', $lic['id']), 'b2b/outgoing/stat/time');
		// $rdb->hdel(sprintf('/license/%s', $lic['id']), 'b2b/outgoing/sync');
		// $rdb->hdel(sprintf('/license/%s', $lic['id']), 'b2b/outgoing/sync/time');

		// $d0 = $rdb->hgetall(sprintf('/license/%s', $lic['id']));
		// if ( ! empty($d0)) {
		// 	$k0 = array_keys($d0);
		// 	sort($k0);
		// 	echo implode(',', $k0);
		// 	// echo "\t";
		// 	// var_dump($d0);
		// 	// exit;
		// }

		switch ($lic['stat']) {
			case 100:
			case 102:
			case 200:
			case 202: // OK
				echo "./bin/cre-ccrs.php verify --license={$lic['id']}  # stat={$lic['stat']}";
				// Ping CRE
				// echo "PING: https://bong.openthc.com/license/{$license['id']}\n";
				// _license_verify_update_stat($dbc, $license, $license['stat']);
				// continue;
				break;
			case 203: // Non-Authoritative Information ??
				break;
			case 402: // Payment
			case 403: // CCRS Authorization
			case 410: // GONE
			case 500: // Some Error
			case 666: // Dead
				// Set the Objects to the Status of the License
				if ($cli_args['--update-stat']) {
					_cre_ccrs_license_status_update_stat($dbc, $lic, $lic['stat']);
					$rdb->del(sprintf('/license/%s', $lic['id']));
				} else {
					echo "./bin/cre-ccrs.php license-status --license={$lic['id']} --update-stat  # stat={$lic['stat']}\n";
				}
				if (666 == $lic['stat']) {
					echo "DELETE Dead Objects\n";
					echo "./bin/license-delete.php {$lic['id']};\n";
					// echo $dbc->_sql_debug('DELETE FROM section WHERE license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM variety WHERE license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM product WHERE license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM crop WHERE license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM lot WHERE license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM inventory_adjust WHERE license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM b2b_incoming_item WHERE b2b_incoming_id IN (SELECT id FROM b2b_incoming WHERE target_license_id = :l0)', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM b2b_incoming WHERE target_license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM b2b_outgoing WHERE source_license_id = :l0', $arg) . "\n";
					// echo $dbc->_sql_debug('DELETE FROM b2b_outgoing_item WHERE b2b_outgoing_id IN (SELECT id FROM b2b_outgoing WHERE source_license_id = :l0)', $arg) . "\n";
				}
				break;
			default:
				echo "UNKNOWN LICENSE STATUS: {$lic['stat']}\n";
		}

		echo "\n";

	}

}



/**
 * Status Set Helper (put on License Object?)
 */
function _cre_ccrs_license_status_update_stat($dbc, $license, $stat)
{
	$sql_list = [];

	// B2B
	$sql_list[] = 'UPDATE b2b_incoming_item SET stat = :s1, data = data #- \'{ "@result" }\' WHERE b2b_incoming_id IN (SELECT id FROM b2b_incoming WHERE target_license_id = :l0)';
	$sql_list[] = 'UPDATE b2b_incoming SET stat = :s1, data = data #- \'{ "@result" }\' WHERE target_license_id = :l0';

	// $sql_list[] = 'UPDATE b2b_outgoing_file SET stat = :s1 WHERE id IN (SELECT id FROM b2b_outgoing WHERE source_license_id = :l0)';
	$sql_list[] = 'UPDATE b2b_outgoing_item SET stat = :s1, data = data #- \'{ "@result" }\' WHERE b2b_outgoing_id IN (SELECT id FROM b2b_outgoing WHERE source_license_id = :l0)';
	$sql_list[] = 'UPDATE b2b_outgoing SET stat = :s1, data = data #- \'{ "@result" }\' WHERE source_license_id = :l0';

	// B2C
	$sql_list[] = 'UPDATE b2c_sale_item SET stat = :s1, data = data #- \'{ "@result" }\' WHERE b2c_sale_id IN (SELECT id FROM b2c_sale WHERE license_id = :l0)';
	$sql_list[] = 'UPDATE b2c_sale SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';

	// Lab Results
	$sql_list[] = 'UPDATE lab_result_metric SET stat = :s1, data = data #- \'{ "@result" }\' WHERE lab_result_id IN (SELECT id FROM lab_result WHERE license_id = :l0)';
	$sql_list[] = 'UPDATE lab_result SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';

	// Crop/Inventory
	$sql_list[] = 'UPDATE crop SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';
	$sql_list[] = 'UPDATE inventory SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';
	$sql_list[] = 'UPDATE inventory_adjust SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';

	$sql_list[] = 'UPDATE product SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';
	$sql_list[] = 'UPDATE section SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';
	$sql_list[] = 'UPDATE variety SET stat = :s1, data = data #- \'{ "@result" }\' WHERE license_id = :l0';

	// $sql_list[] = 'UPDATE license SET stat = 666 WHERE id = :l0';

	foreach ($sql_list as $sql) {
		echo "sql:$sql\n";
		$dbc->query($sql, [
			':l0' => $license['id'],
			':s1' => $stat,
		]);
	}

}
