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
			$dsn_base = "pgsql:host={$cfg_base['hostname']};dbname={$cfg_base['database']};user={$cfg_base['username']};password={$cfg_base['password']}";
			$dbc_base = new SQL($dsn_base);

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

			$_SESSION['sql-conn'] = "pgsql:host={$cfg_user['hostname']};dbname={$cfg_user['database']};user={$cfg_user['username']};password={$cfg_user['password']}";

		}

		$dbc = new SQL($_SESSION['sql-conn']);

		return $dbc;

	}

}
