<?php
/**
 *
 */

namespace Test;

class Base_Case extends \PHPUnit\Framework\TestCase
{
	protected $_pid;
	protected $_tmp_file = '/tmp/pipe-test-case.dat';

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->_pid = getmypid();
	}

	/**
	 * Guzzle Client
	 */
	function _api(): object
	{
		$c = new \GuzzleHttp\Client(array(
			'base_uri' => sprintf('https://%s/', getenv('OPENTHC_TEST_HOST')),
			'allow_redirects' => false,
			'debug' => $_ENV['debug-http'],
			'request.options' => array(
					'exceptions' => false,
			),
			'http_errors' => false,
			'cookies' => true,
		));

		return $c;
	}

}
