<?php
/**
 * Makes sure the Caching Database is present
 */

namespace OpenTHC\Bong\Middleware;

use Edoceo\Radix\DB\SQL;

class Database extends \OpenTHC\Middleware\Base
{
	public function __invoke($REQ, $RES, $NMW)
	{
		if (empty($_SESSION['sql-conn'])) {
			if (empty($_SESSION['sql-name'])) {
				_exit_html('<h1>Invalid Session [LMD-016]</h1><p>you must <a href="/auth/open">sign in</a> again.</p>', 403);
			}
		}

		$dbc = $this->connect();
		$REQ = $REQ->withAttribute('dbc', $dbc);

		$RES = $NMW($REQ, $RES);

		return $RES;

	}

	/**
	 * Connect (or Create) Database
	 */
	function connect()
	{
		if (empty($_SESSION['sql-conn'])) {

			$cfg_base = \OpenTHC\Config::get('database');

			$cfg_user = $cfg_base;
			$cfg_user['database'] = $_SESSION['sql-name'];

			// Connect
			$dsn_base = "pgsql:port=6543;dbname={$cfg_base['database']};user={$cfg_base['username']};password={$cfg_base['password']};application_name=openthc-bong-pool";
			// $dsn_base = sprintf('pgsql:port=6543;dbname=%s;application_name=openthc-bong-pool', $cfg['database']);
			$dbc_base = new SQL($dsn_base);

			// $c = sprintf('pgsql:port=6543;dbname=%s;application_name=openthc-ops', $cfg['database']);
			// $dbc_list[$dsn] = new \Edoceo\Radix\DB\SQL($c, $cfg['username'], $cfg['password']);

			// Create Role
			// $chk = $dbc_base->fetchOne('SELECT rolname FROM pg_roles WHERE rolname = :u0', [ ':u0' => $cfg_user['username'] ]);
			// if (empty($chk)) {
			// 	$dbc_base->query("CREATE ROLE {$cfg_user['username']} WITH LOGIN ENCRYPTED PASSWORD '{$cfg_user['password']}'");
			// }

			// Create Database
			$chk = $dbc_base->fetchOne('SELECT datname FROM pg_database WHERE datname = :d0', [ ':d0' => $cfg_user['database'] ]);
			if (empty($chk)) {
				$dbc_base->query("CREATE DATABASE {$cfg_user['database']} WITH OWNER {$cfg_user['username']} TEMPLATE {$cfg_base['database']} ");
			}

			$_SESSION['sql-conn'] = $dsn_base;

		}

		$dbc = new SQL($_SESSION['sql-conn']);

		return $dbc;

	}

	/**
	 * Create a SQLite database for the back-end
	 */
	function create()
	{
		if (empty($_SESSION['License']['id'])) {
			throw new \Exception('Invalid Session State [LMD-073]');
		}

		$sql_file = sprintf('%s/var/%s.sqlite', APP_ROOT, $_SESSION['License']['id']);
		$sql_good = is_file($sql_file);

		$_SESSION['sql-conn'] = sprintf('sqlite:%s', $sql_file);

		if ( ! $sql_good) {

				$dbc = new \Edoceo\Radix\DB\SQL($_SESSION['sql-conn']);

				$dbc->query('CREATE TABLE base_option (key PRIMARY KEY, val)');

				$dbc->query('CREATE TABLE company (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE license (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE product (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE variety (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE section (id PRIMARY KEY, hash, created_at, updated_at, data)');
				// $dbc->query('CREATE TABLE vehicle (id PRIMARY KEY, hash, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE crop (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE crop_adjust (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE crop_collect (id PRIMARY KEY, hash, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE inventory (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE inventory_adjust (id PRIMARY KEY, hash, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE lab_result (id PRIMARY KEY, hash, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE b2b_incoming (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE b2b_incoming_item (id PRIMARY KEY, b2b_incoming_id, hash, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE b2b_outgoing (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE b2b_outgoing_item (id PRIMARY KEY, b2b_outgoing_id, hash, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE b2c_outgoing (id PRIMARY KEY, hash, created_at, updated_at, data)');
				$dbc->query('CREATE TABLE b2c_outgoing_item (id PRIMARY KEY, b2c_outgoing_id, hash, created_at, updated_at, data)');

				$dbc->query('CREATE TABLE log_audit (id PRIMARY KEY, type, data)');

		}

	}

}
