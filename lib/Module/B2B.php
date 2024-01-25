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

			// Here $this is the Container

			$b2b_type = strtolower($_POST['type']);
			switch ($b2b_type) {
				case 'incoming':
					$subC = new \OpenTHC\Bong\Controller\B2B\Incoming\Update($this);
					$RES = $subC->__invoke($REQ, $RES, $ARG);
					return $RES;
				case 'outgoing':
					$subC = new \OpenTHC\Bong\Controller\B2B\Outgoing\Update($this);
					$RES = $subC->__invoke($REQ, $RES, $ARG);
					return $RES;
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

		// Incoming Search
		$a->get('/incoming', 'OpenTHC\Bong\Controller\B2B\Incoming\Search');

		// Incoming Status
		$a->get('/incoming/status', 'OpenTHC\Bong\Controller\B2B\Incoming\Status');

		// Incoming Single
		$a->get('/incoming/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/incoming/single.php', $REQ, $RES, $ARG);
		});

		$a->post('/incoming/{id}/accept', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/incoming/accept.php', $REQ, $RES, $ARG);
		});

		// Search Outgoing
		$a->get('/outgoing', 'OpenTHC\Bong\Controller\B2B\Outgoing\Search');

		// Outgoing Status
		$a->get('/outgoing/status', 'OpenTHC\Bong\Controller\B2B\Outgoing\Status');

		// Outgoing Single
		$a->get('/outgoing/{id}', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/single.php', $REQ, $RES, $ARG);
		});

		$a->post('/outgoing/{id}/commit', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/commit.php', $REQ, $RES, $ARG);
		});

		// Outgoing Attachments
		$a->get('/outgoing/{id}/file[/{file_id}]', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/outgoing/file.php', $REQ, $RES, $ARG);
		});

		// Search Rejected
		$a->get('/rejected', function($REQ, $RES, $ARG) {
			return _from_cre_file('b2b/rejected/search.php', $REQ, $RES, $ARG);
		});

	}
}
