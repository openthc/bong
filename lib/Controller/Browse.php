<?php
/**
 * App Base Controller
 */

namespace OpenTHC\Bong\Controller;

class Browse extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES, $ARG)
	{
		session_write_close();

		if (empty($_SESSION['sql-conn'])) {
			_exit_html('<h1>Invalid Session</h1><p>you must <a href="/auth/open">sign in</a> again.</p>', 403);
		}

		$data = [];
		$data['Page'] = [ 'title' => 'Browse' ];
		$data['cre_auth'] = $_SESSION['cre-auth'];
		$data['cre_meta_license'] = $_SESSION['cre-auth']['license'];

		$dbc = $REQ->getAttribute('dbc');
		$data['cre_sync'] = $dbc->fetchAll("SELECT * FROM base_option WHERE key LIKE 'sync-%' ORDER BY key");

		switch ($_POST['a']) {
			case 'sync':

				$out_html = [];

				$data = $_SESSION;

				unset($data['_radix']);
				unset($data['crypt-key']);

				$data = json_encode($data, JSON_PRETTY_PRINT);
				$hash = sha1($data);
				$out_html[] = "<p>Generated Hash: $hash</p>";

				$file = sprintf('%s/var/%s.json', APP_ROOT, $hash);
				file_put_contents($file, $data);
				$out_html[] = "<p>Stashed File: $file</p>";

				$cmd = [];
				$cmd[] = sprintf('%s/bin/sync.php --config=%s', APP_ROOT, $file);
				$cmd[] = sprintf('>>%s/var/sync-%s.log', APP_ROOT, $hash);
				$cmd[] = '2>&1';
				$cmd[] = '&';
				$cmd[] = 'echo $!';
				$cmd = implode(' ', $cmd);
				$out_html[] = "<p>Command:</p><pre>$cmd</pre>";

				// $buf = shell_exec($cmd);
				$out_html[] = "<p>Output <small>(pid)</small>:</p><pre>$buf</pre>";

				// Alert?
				$out_html[] = '<p>Sync Started. Back to <a href="/browse">/browse</a>.</p>';

				_exit_html(implode("\n", $out_html));

		}

		$html = $this->render('browse.php', $data);

		return $RES->write($html);

	}
}
