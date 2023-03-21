<?php
/**
 * PHPUnit Test Bootstrap file
 *
 *
 */

// Load App bootstrap file
require_once(dirname(dirname(__FILE__)) . '/boot.php');

// Workaround for JWT class
$_SERVER['SERVER_NAME'] = 'bong.openthc.dev';
