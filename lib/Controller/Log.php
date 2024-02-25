<?php
/**
 * View the Logs from the STEM service
 */

namespace OpenTHC\Bong\Controller;

class Log extends \OpenTHC\Controller\Base
{
	private $query_limit = 25;
	private $query_offset = 0;

	private $sql_debug;

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		session_write_close();

		if (empty($_SESSION['sql-conn'])) {
			_exit_html('<h1>Invalid Session</h1><p>you must <a href="/auth/open">sign in</a> again.</p>', 403);
		}

		$this->query_offset = max(0, intval($_GET['o']));

		$data = [
			'Page' => [ 'title' => 'Log Search :: OpenTHC BONG' ],
			'tz' => \OpenTHC\Config::get('tz'),
			'link_newer' => http_build_query(array_merge($_GET, [ 'o' => max(0, $this->query_offset - $this->query_limit) ] )),
			'link_older' => http_build_query(array_merge($_GET, [ 'o' => $this->query_offset + $this->query_limit ] )),
		];

		// Log Delta
		if (false) {
			$res = $this->_sql_query($REQ);
			$data['log_delta'] = $res;
			$data['sql_debug'] = $this->sql_debug;
		}

		// Log Upload
		$res = $this->_sql_query_log_request($REQ);
		$data['log_upload'] = $res;
		$data['sql_debug'] =$this->sql_debug;

		$html = $this->render('log.php', $data);

		if ('snap' == $_GET['a']) {
			$output_snap = _ulid();
			$output_file = sprintf('%s/webroot/snap/%s.html', APP_ROOT, $output_snap);
			$output_link = sprintf('/snap/%s.html', $output_snap);
			$output_html = preg_replace('/<form.+<\/form>/', '', $output_html);
			$output_html = preg_replace('/<div class="sql-debug">.+?<\/div>/', '', $output_html);
			file_put_contents($output_file, $output_html);
			return $RES->withRedirect($output_link);
		}

		return $RES->write($html);

	}

	/**
	 *
	 */
	function single($REQ, $RES, $ARG)
	{
		switch ($_GET['type']) {
			case 'delta':
				break;
			case 'upload':

				$dbc = $REQ->getAttribute('dbc');
				$res = $dbc->fetchRow('SELECT *, (updated_at - created_at) AS elapsed_ms  FROM log_upload WHERE id = :pk', [ ':pk' => $ARG['id'] ]);
				$res['License'] = $dbc->fetchRow('SELECT * FROM license WHERE id = :l0', [ ':l0' => $res['license_id'] ]);
				$res['source_data'] = json_decode($res['source_data'], true);
				$res['result_data'] = json_decode($res['result_data'], true);

				$data = [];
				$data['Page'] = [ 'title' => "Upload {$res['id']} / {$res['name']} = {$res['stat']}" ];
				$data['log_upload'] = $res;

				$html = $this->render('log/single-upload.php', $data);

				if ('log-snap' == $_POST['a']) {
					$output_ulid = _ulid();
					$output_file = sprintf('%s/webroot/pub/%s.html', APP_ROOT, $output_ulid);
					$output_link = sprintf('/pub/%s.html', $output_ulid);
					$output_html = preg_replace('/<nav.+\/nav>/ms', '', $html);
					$output_html = preg_replace('/<form.+\/form>/ms', '', $output_html);
					// $output_html = preg_replace('/<section.+?Record Dump.+?\/section>/ms', '', $output_html);
					file_put_contents($output_file, $output_html);
					return $RES->withRedirect($output_link);
				}

				return $RES->write( $html );

		}

	}

	/**
	 * Run the Actual Query
	 */
	function _sql_query($REQ)
	{
		$dbc = $REQ->getAttribute('dbc');

		$arg = [];
		$sql = 'SELECT * FROM log_delta {WHERE} ';
		$sql_filter = [];

		// Specific Delta ID?
		if (!empty($_GET['id'])) {
			// @todo Allow Comma Separated List
			$sql = str_replace('{WHERE}', 'WHERE id = :pk', $sql);
			$arg = [ ':pk' => $_GET['id'] ];
			$this->sql_debug = $dbc->_sql_debug($sql, $arg);
			$res = $dbc->fetchAll($sql, $arg);
			return $res;
		}

		// Subject
		if (!empty($_GET['subject'])) {
			$sql_filter[] = 'subject = :s0';
			$arg[':s0'] = $_GET['subject'];
		}

		// Date Lo
		if (!empty($_GET['d0']) || !empty($_GET['t0'])) {

			$ts = trim(sprintf('%s %s', $_GET['d0'], $_GET['t0']));
			$dt = new \DateTime($ts);

			$sql_filter[] = 'created_at >= :dt0';
			$arg[':dt0'] = $dt->format(\DateTime::RFC3339);

		}

		// Date Hi
		if (!empty($_GET['dt1'])) {

			$dt = new \DateTime($_GET['dt1']);
			$sql_filter[] = 'id <= :dt1';
			$arg[':dt1'] = $dt->format(\DateTime::RFC3339);

		}

		// General Query
		if (!empty($_GET['q'])) {
			$sql_filter[] = 'subject_id = :s1';
			$arg[':s1'] = $_GET['q'];
		}

		// Build Filter
		if (count($sql_filter)) {
			$sql_filter = implode(' AND ' , $sql_filter);
			$sql = str_replace('{WHERE}', sprintf('WHERE %s', $sql_filter), $sql);
		} else {
			$sql = str_replace('{WHERE}', '', $sql);
		}

		$sql.= ' ORDER BY created_at DESC';
		$sql.= sprintf(' LIMIT %d', $this->query_limit);
		$sql.= sprintf(' OFFSET %d', $_GET['o']);

		$this->sql_debug = $dbc->_sql_debug($sql, $arg);

		$res = $dbc->fetchAll($sql, $arg);

		return $res;

	}

	/**
	 * Search the CCRS Log Upload
	 */
	function _sql_query_log_request($REQ)
	{
		$dbc = $REQ->getAttribute('dbc');

		$sql = <<<SQL
		SELECT log_upload.id, log_upload.created_at, log_upload.updated_at, log_upload.name, log_upload.stat
			-- , length(source_data::text) AS source_data_length
			-- , length(result_data::text) AS result_data_length
			, license.id AS license_id
			, license.name AS license_name
			-- , jsonb_object_keys(source_data) AS source_keys
			-- , jsonb_object_keys(result_data) AS result_keys
		FROM log_upload
		LEFT JOIN license ON log_upload.license_id = license.id
		{WHERE}
		SQL;

		$sql_param = [];
		$sql_where = [];

		if ( ! empty($_SESSION['License']['id'])) {
			$sql_param[':l0'] = $_SESSION['License']['id'];
			$sql_where[] = 'log_upload.license_id = :l0';
		}

		// Specific Delta ID?
		if (!empty($_GET['id'])) {
			// @todo Allow Comma Separated List
			$sql = str_replace('{WHERE}', 'WHERE id = :pk', $sql);
			$arg = [ ':pk' => $_GET['id'] ];
			$this->sql_debug = $dbc->_sql_debug($sql, $arg);
			$res = $dbc->fetchAll($sql, $arg);
			return $res;
		}

		// Subject
		if ( ! empty($_GET['subject'])) {
			$sql_param[':s0'] = $_GET['subject'];
			$sql_where[] = 'subject = :s0';
		}

		// Subject ID
		if (!empty($_GET['q'])) {
			$sql_param[':s1'] = sprintf('%%%s%%', $_GET['q']);
			$sql_where[] = ' (source_data::text LIKE :s1 OR result_data::text LIKE :s1 OR req_info::text LIKE :s1 OR res_info::text LIKE :s1)';
			//  subject_id = :s1 ';
		}
		// source_data | jsonb
		// result_data | jsonb
		// stat        | integer
		// updated_at  | timestamp w
		// license_id  | character v
		// req_info    | jsonb
		// res_info    | jsonb


		// Date Lo
		if (!empty($_GET['d0'])) { // } || !empty($_GET['t0'])) {

			$ts = trim(sprintf('%s %s', $_GET['d0'], $_GET['t0']));
			$dt = new \DateTime($ts);

			$sql_param[':dt0'] = $dt->format(\DateTime::RFC3339);
			$sql_where[] = 'log_upload.created_at >= :dt0';

		}

		// Date Hi
		if (!empty($_GET['d1'])) {

			$dt = new \DateTime($_GET['d1']);
			$sql_param[':dt1'] = $dt->format(\DateTime::RFC3339);
			$sql_where[] = 'log_upload.created_at <= :dt1';

		}

		// Build Filter
		if (count($sql_where)) {
			$sql_where = implode(' AND ' , $sql_where);
			$sql = str_replace('{WHERE}', sprintf('WHERE %s', $sql_where), $sql);
		} else {
			$sql = str_replace('{WHERE}', '', $sql);
		}

		$sql.= ' ORDER BY log_upload.created_at DESC';
		$sql.= sprintf(' LIMIT %d', $this->query_limit);
		$sql.= sprintf(' OFFSET %d', $_GET['o']);

		$this->sql_debug = $dbc->_sql_debug($sql, $sql_param);

		$res = $dbc->fetchAll($sql, $sql_param);

		return $res;

	}

}
