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
var_dump($cli_args);


define('OPENTHC_TEST_OUTPUT_BASE', \OpenTHC\Test\Helper::output_path_init());


// Call PHPCS?
// \OpenTHC\Test\Linter::execute();

// Call Linter?
$path_list = [ 'boot.php', 'bin', 'lib', 'sbin', 'test', 'view', 'webroot' ];
foreach ($path_list as $path) {

	// $rdi = new \RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME);
	// $rii = new \RecursiveIteratorIterator($rdi, \RecursiveIteratorIterator::CHILD_FIRST);

	// Recursive Iterator
	$cmd = [];
	$cmd[] = 'php';
	$cmd[] = '-l';
	$cmd[] = escapeshellarg($file);
	// $buf = system($cmd);
	// if (strlen($buf)) {
	// 	// Failed:
	// 	echo $path . "\n" . "$buf";
	// }
}

// $tc = new OpenTHC\Test\PHPStan();
// $tc->execute();

// Call Static Analyser?
$stan_config = <<<CFG
parameters:
	bootstrapFiles:
		- ../boot.php
	level: 6
	paths:
		- ../bin
		- ../etc
		- ../lib
		- ../sbin
		- ../test
		- ../view
		- ../not-real
	ignoreErrors:
		- '/Undefined variable: \$this/'
		- '/Using \$this outside a class/'
		- '/Variable \$this might not be defined/'
	tipsOfTheDay: false
CFG;

file_put_contents(sprintf('%s/test/phpstan.neon', APP_ROOT, $stan_config));

$cmd = [];
$cmd[] = sprintf('%s/vendor/bin/phpstan', APP_ROOT);
$cmd[] = 'analyze';
$cmd[] = '--configuration=test/phpstan.neon';
$cmd[] = '--error-format=junit';
$cmd[] = '--no-ansi';
$cmd[] = '--no-progress';
$cmd[] = '2>&1';
$cmd = implode(' ', $cmd);
echo "cmd:$cmd\n";
$out = null;
$res = null;
// $buf = exec($cmd, $out, $res);
// var_dump($res);
// var_dump($buf);
// file_put_contents(sprintf('%s/phpstan.xml', OPENTHC_TEST_OUTPUT_BASE), implode("\n", $out));
// _xsl_transform(sprintf('%s/phpstan.xml', OPENTHC_TEST_OUTPUT_BASE), sprintf('%s/phpstan.html', OPENTHC_TEST_OUTPUT_BASE));


// Psalm/Psalter?


// PHPUnit
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

$host = \OpenTHC\Config::get('openthc/bong/origin');
var_dump($host);
$path = str_replace(sprintf('%s/webroot/', APP_ROOT), '', OPENTHC_TEST_OUTPUT_BASE);
var_dump($path);

// echo sprintf('TEST COMPLETE %s/output/test-report/', \OpenTHC\Config::get('openthc/bong/origin'));
echo "TEST COMPLETE\n  $host/$path\n";
// echo "\n";

function proc_exec($cmd) {

	$io_want = [
		0 => [ 'pipe', 'r' ],
		1 => [ 'pipe', 'w' ],
		2 => [ 'pipe', 'w' ],
	];

	$sub = proc_open($cmd, $io_want, $io_pipe);

	// Read in RealTime and Output
	// Copy to File?

}
