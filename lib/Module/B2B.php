<?php
/**
 * B2B Wholesale Sales
 */

namespace OpenTHC\Bong\Module;

class B2B extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM b2b_sale ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

		$a->get('/outgoing', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/search.php', $RES, $ARG);
		});

		$a->get('/outgoing/{guid:[\w\.]+}', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/single.php', $RES, $ARG);
		});

		//$this->post('/outgoing/{guid:[\w\.]+}/accept', function($REQ, $RES, $ARG) {
		//	$f = sprintf('%s/controller/%s/transfer-accept.php', APP_ROOT, $_SESSION['cre-base']);
		//	$RES = require_once($f);
		//	return $RES;
		//});

		/*
			Incoming Transfers
		*/
		$a->get('/incoming', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/incoming/search.php', $RES, $ARG);
		});

		$a->get('/incoming/{guid:[\w\.]+}', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/single.php', $RES, $ARG);
		});

		$a->post('/incoming/{guid:[\w\.]+}/accept', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/incoming/accept.php', $RES, $ARG);
		});

		/*
			Rejected Transfers
		*/
		$a->get('/rejected', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/rejected/search.php', $RES, $ARG);
		});

	}
}
