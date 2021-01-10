<?php
/**
 * App Base Controller
 */

namespace OpenTHC\Bong\Controller;

class Browse extends \OpenTHC\Bong\Controller\Base
{
	function __invoke($REQ, $RES, $ARG)
	{
		session_write_close();

		$data = [];
		$data['Page'] = [ 'title' => 'Browse' ];
		$data['cre_auth'] = $_SESSION['cre-auth'];
		$data['cre_meta_license'] = $_SESSION['cre-auth']['license'];

		switch ($_POST['a']) {
			case 'sync':

				$data = $_SESSION;

				unset($data['_radix']);
				unset($data['crypt-key']);
				// unset($data['sql-good']);
				// unset($data['sql-name']);
				// unset($data['sql-conn'])

				$data = json_encode($data, JSON_PRETTY_PRINT);
				$hash = sha1($data);

				$file = sprintf('%s/var/%s.json', APP_ROOT, $hash);

				file_put_contents($file, $data);

				$cmd = [];
				$cmd[] = sprintf('%s/bin/sync.php --config=%s', APP_ROOT, $file);
				$cmd[] = '2>&1';
				$cmd[] = sprintf('>%s/var/sync-%s.log', APP_ROOT, $hash);
				$cmd[] = '&';
				$cmd[] = 'echo $!';
				$cmd = implode(' ', $cmd);
				var_dump($cmd);

				$buf = shell_exec($cmd);
				var_dump($buf);

				// Alert?
				_exit_html('<p>Sync Started. Back to <a href="/browse">/browse</a>.</p>');

				exit(0);

		}

		return $this->render($RES, 'browse.php', $data);

	}
}
