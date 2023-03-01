<?php
/**
 * B2B Wholesale Sales
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Module;

class B2B extends \OpenTHC\Module\Base
{
	/**
	 *
	 */
	function __invoke($a)
	{
		// Main
		$a->get('', function($REQ, $RES, $ARG) {

			$dbc = $REQ->getAttribute('dbc');
			$res = $dbc->fetchAll('SELECT id, hash, updated_at FROM b2b_sale ORDER BY updated_at DESC');

			return $RES->withJSON($res);

		});

		// Create
		$a->post('', function($REQ, $RES, $ARG) {

			$b2b_type = strtolower($_POST['type']);
			switch ($b2b_type) {
				case 'incoming':
				case 'outgoing':
					return _from_cre_file(sprintf('b2b/%s/create.php', $b2b_type), $REQ, $RES, $ARG);
			}

			// Fail
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'detail' => 'Invalid Request [B2B-040]' ],
			], 400);

		});

		// Update
		$a->post('/{id}', function($REQ, $RES, $ARG) {

			$b2b_type = strtolower($_POST['type']);
			switch ($b2b_type) {
				case 'incoming':
				case 'outgoing':
					return _from_cre_file(sprintf('b2b/%s/update.php', $b2b_type), $REQ, $RES, $ARG);
			}

			// Fail
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'detail' => 'Invalid Request [B2B-058]' ],
			], 400);

		});

		// Commit
		$a->post('/{id}/commit', function($REQ, $RES, $ARG) {

			$_POST['type'] = 'outgoing';
			$b2b_type = strtolower($_POST['type']);
			switch ($b2b_type) {
				// case 'incoming':
				case 'outgoing':
					return _from_cre_file(sprintf('b2b/%s/commit.php', $b2b_type), $REQ, $RES, $ARG);
			}

			// Fail
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'detail' => 'Invalid Request [B2B-076]' ],
			], 400);

		});

		// Search Incoming
		$a->get('/incoming', 'OpenTHC\Bong\Controller\B2B\Incoming\Search');

		$a->get('/incoming/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/single.php', $REQ, $RES, $ARG);
		});

		$a->post('/incoming/{id}/accept', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/incoming/accept.php', $REQ, $RES, $ARG);
		});

		// Search Outgoing
		$a->get('/outgoing', 'OpenTHC\Bong\Controller\B2B\Outgoing\Search');
		// function($REQ, $RES, $ARG) {
		// 	return _from_cre_file('b2b/outgoing/search.php', $REQ, $RES, $ARG);
		// });

		$a->get('/outgoing/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/single.php', $REQ, $RES, $ARG);
		});

		$a->post('/outgoing/{id}/commit', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/commit.php', $REQ, $RES, $ARG);
		});

		// Search Rejected
		$a->get('/rejected', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/rejected/search.php', $REQ, $RES, $ARG);
		});

	}
}
