<?php
/**
 * OpenTHC BONG Application Bootstrap
 *
 * SPDX-License-Identifier: MIT
 */

define('APP_ROOT', __DIR__);

error_reporting(E_ALL & ~ E_NOTICE);

openlog('openthc-bong', LOG_ODELAY|LOG_PID, LOG_LOCAL0);

require_once(APP_ROOT . '/vendor/autoload.php');

if ( ! \OpenTHC\Config::init(APP_ROOT) ) {
	_exit_html_fail('<h1>Invalid Application Configuration [ALB-035]</h1>', 500);
}


/**
 * Database Connection
 */
function _dbc()
{
	static $ret;

	if (empty($ret)) {

		$cfg = \OpenTHC\Config::get('database');
		$cfg['database'] = 'openthc_bong_ccrs';
		$dsn = sprintf('pgsql:host=%s;dbname=%s', $cfg['hostname'], $cfg['database']);
		$ret = new \Edoceo\Radix\DB\SQL($dsn, $cfg['username'], $cfg['password']);

	}

	return $ret;

}


/**
 * Hands work Directly to View Script
 */
function _from_cre_file($f0, $REQ, $RES, $ARG)
{
	$f0 = trim($f0, '/');
	$f1 = sprintf('%s/lib/CRE/%s/%s', APP_ROOT, $_SESSION['cre']['engine'], $f0);
	if ( ! is_file($f1)) {

		return $RES->withJSON([
			'data' => null,
			'meta' => [
				'cre' => $_SESSION['cre']['engine'],
				'detail' => 'Interface not implemented [APP#046]',
			]
		], 501);

	}

	$out = require_once($f1);

	return $out;

}

/**
 * Create Object Status Table
 */
function object_status_table($html)
{
	if (empty($html)) {
		return '<strong>No Data</strong>';
	}

	ob_start();
	echo '<table class="table table-sm">';
	echo '<thead class="table-dark">';
	echo '<tr><th style="width: 8em;">Status</th><th style="width: 8em;">Count</th><th>Errors</th></tr></thead>';
	echo '<tbody>';
	echo $html;
	echo '</tbody>';
	echo '</table>';

	return ob_get_clean();

}


/**
 * Output Helper
 */
function object_status_tbody($obj, $res)
{
	if (empty($res)) {
		return null;
	}

	$ret = [];
	foreach ($res as $rec) {
		$ret[] = sprintf('<tr><td><a href="/%s?stat=%d">%d</a></td><td class="r">%d</td><td><a href="/%s?q=%s">%s</a></td></tr>'
			, $obj
			, $rec['stat']
			, $rec['stat']
			, $rec['c']
			, $obj
			, rawurlencode($rec['e'])
			, __h($rec['e'])
		);
	}

	return $ret;

}


/**
 * Set Option
 */
function _set_option($dbc, $key, $val)
{
	if ($dbc->fetchOne("SELECT id FROM base_option WHERE key = :k", [ ':k' => $key ])) {
		$dbc->update('base_option', [ 'val' => $val ], [ 'key' => $key ]);
	} else {
		$dbc->insert('base_option', [
			'id' => _ulid(),
			'key' => $key,
			'val' => $val,
		]);
	}
}
