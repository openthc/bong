<?php
/**
 * License Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\License;

class Update extends \OpenTHC\Bong\Controller\Base\Update
{
	protected $_tab_name = 'license';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		if ('current' == $ARG['id']) {
			$ARG['id'] = $_SESSION['License']['id'];
		}

		$dbc = $REQ->getAttribute('dbc');

		// If POST from Browser do this
		$License = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
		if (empty($License['id'])) {
			throw new \Exception("Invalid License '{$ARG['id']}' [CLU-026]");
		}

		switch ($_POST['a']) {
			case 'license-cache-clear':
				$this->post_cache_clear($License);
				return $RES->withRedirect(sprintf('/license/%s', $License['id']));
				break;
			case 'license-error-reset':
				return $this->error_reset($RES, $dbc, $License);
				break;
			case 'license-verify':
				$req_ulid = $this->post_verify($dbc, $License);
				// return $RES->withJSON([
				// 	'data' => $req_ulid,
				// 	'meta' => [],
				// ], 201);
				return $RES->withRedirect(sprintf('/license/%s', $License['id']));
				break;
		}


		if ($ARG['id'] != $_SESSION['License']['id']) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [
					'note' => 'Not Authorized [CLU-027]'
				]
			], 403, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		$ret = [];
		$ret['data'] = null;
		$ret['meta'] = [];

		$ret_code = 200;

		$dbc = $REQ->getAttribute('dbc');
		$res = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $ARG['id'] ]);
		if (empty($res['id'])) {
			$ret['meta']['note'] = 'License Not Found';
			$ret_code = 404;
		} else {
			$res = $dbc->query('UPDATE license SET code = :c0, name = :n0, company_id = :company_id WHERE id = :l0', [
				':company_id' => $_POST['company_id'],
				':l0' => $_SESSION['License']['id'],
				':c0' => trim($_POST['code']),
				':n0' => trim($_POST['name']),
			]);
			$ret['data'] = $res;
		}

		return $RES->withJSON($ret, $ret_code, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}

	/**
	 *
	 */
	function error_reset($RES, $dbc, $License) {

		$stat_in = 'stat (400, 402, 403)';
		$sql_update = 'UPDATE %s SET stat = 100 WHERE license_id = :l0 AND %s';

		$sql_list = [];
		$sql_list[] = sprintf($sql_update, 'variety', $stat_in);
		$sql_list[] = sprintf($sql_update, 'section', $stat_in);
		$sql_list[] = sprintf($sql_update, 'product', $stat_in);
		$sql_list[] = sprintf($sql_update, 'crop', $stat_in);
		$sql_list[] = sprintf($sql_update, 'inventory', $stat_in);

		$sql_list[] = "UPDATE b2b_incoming SET stat = 100 WHERE target_license_id = :l0 AND {$stat_in}";
		$sql_list[] = <<<SQL
		UPDATE b2b_incoming_item SET stat = 100 WHERE b2b_incoming_id IN (
			SELECT id FROM b2b_incoming WHERE target_license_id = :l0 AND $stat_in
		)
		SQL;

		$sql_list[] = "UPDATE b2b_outgoing SET stat = 100 WHERE source_license_id = :l0 AND $stat_in";
		$sql_list[] = <<<SQL
		UPDATE b2b_outgoing_item SET stat = 100 WHERE b2b_incoming_id IN (
			SELECT id FROM b2b_incoming WHERE target_license_id = :l0 AND $stat_in
		)
		SQL;

		$arg = [
			':l0' => $License['id'],
		];
		foreach ($sql_list as $sql) {
			$dbc->query($sql, $arg);
		}

		return $RES->withRedirect(sprintf('/license/%s', $License['id']));

	}

	/**
	 *
	 */
	function post_cache_clear($License)
	{
		$rdb = \OpenTHC\Service\Redis::factory();
		$d0 = $rdb->del(sprintf('/license/%s', $License['id']));
	}

	function post_verify($dbc, $License)
	{
		$dbc->query('UPDATE license SET stat = 102 WHERE id = :l0', [ ':l0' => $License['id'] ]);

		$req_ulid = _ulid();
		$req_code = "SECTION UPLOAD $req_ulid";

		$csv_data = [];
		// $csv_data[] = [ '-canary-', $req_code, 'FALSE', '-canary-', '-canary-', date('m/d/Y'), '-canary-', date('m/d/Y'), 'UPDATE' ];
		$csv_data[] = [
			$License['code']
			, 'OPENTHC SECTION PING'
			, 'FALSE'
			, 'OPENTHC SECTION PING'
			, '-system-'
			, date('m/d/Y')
			, '-system-'
			, date('m/d/Y')
			, 'DELETE'
		];

		$cre_service_key = \OpenTHC\Config::get('cre/usa/wa/ccrs/service-key');
		$csv_name = sprintf('Area_%s_%s.csv', $cre_service_key, $req_ulid);
		$csv_head = explode(',', 'LicenseNumber,Area,IsQuarantine,ExternalIdentifier,CreatedBy,CreatedDate,UpdatedBy,UpdatedDate,Operation');
		$col_size = count($csv_head);
		$row_size = count($csv_data);
		$csv_temp = fopen('php://temp', 'w');

		// Output
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedBy',   'OpenTHC' ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'SubmittedDate', date('m/d/Y') ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values(array_pad([ 'NumberRecords', $row_size ], $col_size, '')));
		\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, array_values($csv_head));
		foreach ($csv_data as $row) {
			\OpenTHC\CRE\CCRS::fputcsv_stupidly($csv_temp, $row);
		}
		fseek($csv_temp, 0);

		// Add to Database
		$rec = [];
		$rec['id'] = $req_ulid;
		$rec['license_id'] = $License['id'];
		$rec['name'] = $req_code;
		$rec['source_data'] = json_encode([
			'name' => $csv_name,
			'data' => stream_get_contents($csv_temp)
		]);

		$dbc->insert('log_upload', $rec);

		return $req_ulid;

	}

}
