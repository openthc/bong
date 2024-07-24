#!/usr/bin/php
<?php
/**
 *
 */

require_once(dirname(__DIR__) . '/boot.php');

// $arg = \OpenTHC\Docopt::parse($doc, ?$argv=[]);
// Parse CLI
$doc = <<<DOC
OpenTHC BONG Test

Usage:
	test [options]

Options:
	--filter=<FILTER>   Some Filter for PHPUnit

DOC;

$arg = Docopt::handle($doc, [
	'help' => false,
	'optionsFirst' => true,
]);
$cli_args = $arg->args;
// var_dump($cli_args);


define('OPENTHC_TEST_OUTPUT_BASE', \OpenTHC\Test\Helper::output_path_init());

// Bootstrap Data
// \OpenTHC\Test\Helper\DataLoad::load('file.yaml');


// Call Linter?
$tc = new \OpenTHC\Test\Facade\PHPLint([
	'output' => OPENTHC_TEST_OUTPUT_BASE
]);
// $res = $tc->execute();
// var_dump($res);


// Call PHPCS?
// \OpenTHC\Test\Facade\PHPCS::execute();


// PHPStan
$tc = new OpenTHC\Test\Facade\PHPStan([
	'output' => OPENTHC_TEST_OUTPUT_BASE
]);
// $res = $tc->execute();
// var_dump($res);


// Psalm/Psalter?
// $tc = new OpenTHC\Test\Facade\Psalm($cfg);
// $res = $tc->execute();
// var_dump($res);


// PHPUnit
// $cfg = [];
// $tc = new OpenTHC\Test\Facade\PHPUnit($cfg);
// $res = $tc->execute();
// var_dump($res);

$arg = [];
$arg[] = 'phpunit';
$arg[] = '--configuration';
$arg[] = sprintf('%s/test/phpunit.xml', APP_ROOT);
// $arg[] = '--coverage-xml';
// $arg[] = sprintf('%s/coverage', OPENTHC_TEST_OUTPUT_BASE);
$arg[] = '--log-junit';
$arg[] = sprintf('%s/phpunit.xml', OPENTHC_TEST_OUTPUT_BASE);
$arg[] = '--testdox-html';
$arg[] = sprintf('%s/testdox.html', OPENTHC_TEST_OUTPUT_BASE);
$arg[] = '--testdox-text';
$arg[] = sprintf('%s/testdox.txt', OPENTHC_TEST_OUTPUT_BASE);
$arg[] = '--testdox-xml';
$arg[] = sprintf('%s/testdox.xml', OPENTHC_TEST_OUTPUT_BASE);
// // Filter?
if ( ! empty($cli_args['--filter'])) {
	$arg[] = '--filter';
	$arg[] = $cli_args['--filter'];
}

ob_start();
$cmd = new \PHPUnit\TextUI\Command();
$res = $cmd->run($arg, false);
var_dump($res);
// 0 == success
// 1 == ?
// 2 == Errors
$data = ob_get_clean();
switch ($res) {
case 0:
	$data.= "\nTEST SUCCESS\n";
	break;
case 1:
	$data.= "\nTEST FAILURE\n";
	break;
case 2:
	$data.= "\nTEST FAILURE (ERRORS)\n";
	break;
default:
	$data.= "\nTEST UNKNOWN ($res)\n";
	break;
}
$file = sprintf('%s/phpunit.txt', OPENTHC_TEST_OUTPUT_BASE);
file_put_contents($file, $data);

// PHPUnit Transform
$source = sprintf('%s/phpunit.xml', OPENTHC_TEST_OUTPUT_BASE);
$output = sprintf('%s/phpunit.html', OPENTHC_TEST_OUTPUT_BASE);
\OpenTHC\Test\Helper::xsl_transform($source, $output);


// Done
\OpenTHC\Test\Helper::index_create($html);

$origin = \OpenTHC\Config::get('openthc/bong/origin');
$output = str_replace(sprintf('%s/webroot/', APP_ROOT), '', OPENTHC_TEST_OUTPUT_BASE);

echo "TEST COMPLETE\n  $origin/$output\n";
