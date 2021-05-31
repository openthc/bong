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
		if (empty($_SESSION['sql-conn'])) {
			_exit_html('<h1>Invalid Session</h1><p>you must <a href="/auth/open">sign in</a> again.</p>', 403);
		}

		$this->query_offset = max(0, intval($_GET['o']));

		$res = $this->_sql_query($REQ);

		$data = [
			'Page' => [ 'title' => 'Log Search :: OpenTHC BONG' ],
			'tz' => \OpenTHC\Config::get('tz'),
			'link_newer' => http_build_query(array_merge($_GET, [ 'o' => max(0, $this->query_offset - $this->query_limit) ] )),
			'link_older' => http_build_query(array_merge($_GET, [ 'o' => $this->query_offset + $this->query_limit ] )),
			'log_delta' => $res,
			'sql_debug' => $this->sql_debug,
		];

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

		// Subject ID
		if (!empty($_GET['q'])) {
			$sql_filter[] = 'subject_id = :s1';
			$arg[':s1'] = $_GET['q'];
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

}
