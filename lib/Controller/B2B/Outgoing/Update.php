<?php
/**
 * UPSERT B2B Outgoing Records
 *
 * SPDX-License-Identifier: MIT
 */


namespace OpenTHC\Bong\Controller\B2B\Outgoing;

class Update extends \OpenTHC\Bong\Controller\Base\Update
{
	public $_tab_name = 'b2b_outgoing_item';

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$have = $want = 0;

		$dbc = $REQ->getAttribute('dbc');

		$sql = 'SELECT id, source_license_id FROM b2b_outgoing WHERE id = :s0 ';
		$arg = [ ':s0' => $ARG['id'] ];
		$chk = $dbc->fetchRow($sql, $arg);
		if ( ! empty($chk['id'])) {

			if ($chk['source_license_id'] != $_SESSION['License']['id']) {
				return $RES->withJSON([
					'data' => $ARG['id'],
					'meta' => [
						'note' => 'Access Denied [BOU-026]'
					],
				], 409);
			}

		}

		// Target License Check
		if (empty($_POST['target']['id'])) {
			return $RES->withJSON([
				'data' => $ARG['id'],
				'meta' => [
					'note' => 'Invalid Target License; Invalid ID [BOU-032]'
				]
			], 400);
		}
		if (strlen($_POST['target']['code']) > 10) {
			return $RES->withJSON([
				'data' => $ARG['id'],
				'meta' => [
					'note' => 'Invalid Target License; Invalid Code [BOU-041]'
				]
			], 400);
		}
		if (empty($_POST['target']['phone'])) {
			return $RES->withJSON([
				'data' => $ARG['id'],
				'meta' => [
					'note' => 'Invalid Target License; Phone Required [BOU-049]'
				]
			], 400);
		}
		if (empty($_POST['target']['email'])) {
			return $RES->withJSON([
				'data' => $ARG['id'],
				'meta' => [
					'note' => 'Invalid Target License; Email Required [BOU-057]'
				]
			], 400);
		}


		// UPSERT B2B Outgoing
		$sql = <<<SQL
		INSERT INTO b2b_outgoing (id, source_license_id, target_license_id, created_at, name, hash, data)
		VALUES (:o1, :sl0, :tl0, :ct0, :n0, :h0, :d0)
		ON CONFLICT (id, source_license_id) DO
		UPDATE SET target_license_id = :tl0, created_at = :ct0, updated_at = now(), stat = 100, name = :n0, hash = :h0, data = coalesce(b2b_outgoing.data, '{}'::jsonb) || :d0
		SQL;

		$arg = [
			':o1' => $ARG['id'],
			':sl0' => $_SESSION['License']['id'],
			':tl0' => $_POST['target']['id'],
			':ct0' => $_POST['created_at'],
			':n0' => $_POST['name'],
			':d0' => json_encode([
				'@version' => 'openthc/2015',
				'@source' => $_POST
			]),
		];
		$arg[':h0'] = sha1($arg[':d0']);

		$want++;
		$ret = $dbc->query($sql, $arg);
		if (1 == $ret) {
			$have++;
			// return $RES->withJSON([
			// 	'data' => [
			// 		'id' => $ARG['id'],
			// 		'name' => $_POST['name']
			// 	],
			// 	'meta' => $_POST,
			// ]);
		}

		$b2b_ret = [];
		$b2b_ret['id'] = $arg[':o1'];
		$b2b_ret['item_list'] = [];

		// UPSERT B2B Outgoing Item
		foreach ($_POST['item_list'] as $b2b_item) {

			$sql = <<<SQL
			INSERT INTO b2b_outgoing_item (id, b2b_outgoing_id, name, hash, data) VALUES (:o1, :b2b1, :n0, :h0, :d0)
			ON CONFLICT (id) DO
			UPDATE SET updated_at = now(), stat = 100, name = :n0, hash = :h0, data = coalesce(b2b_outgoing_item.data, '{}'::jsonb) || :d0
			WHERE b2b_outgoing_item.id = :o1 AND b2b_outgoing_item.b2b_outgoing_id = :b2b1
			SQL;

			$arg = [
				':o1' => $b2b_item['id'],
				':b2b1' => $b2b_ret['id'],
				':n0' => $b2b_item['id'],
				':d0' => json_encode([
					'@version' => 'openthc/2015',
					'@source' => $b2b_item
				]),
			];
			$arg[':h0'] = sha1($arg[':d0']);

			$want++;
			$ret = $dbc->query($sql, $arg);
			if (1 == $ret) {
				$have++;
				$b2b_ret['item_list'][] = [
					'id' => $arg[':o1'],
					'stat' => 200,
				];
				// return $RES->withJSON([
				// 	'data' => [
				// 		'id' => $ARG['id'],
				// 		'name' => $_POST['name']
				// 	],
				// 	'meta' => $_POST,
				// ]);
			} else {
				$b2b_ret['item_list'][] = [
					'id' => $arg[':o1'],
					'stat' => 500,
				];
			}

		}

		if ($want > 0) {

			if ($have == $want) {
				return $RES->withJSON([
					'data' => $b2b_ret,
					'meta' => null, // $_POST,
				]);
			}

			return $RES->withJSON([
				'data' => $b2b_ret,
				'meta' => null, // $_POST,
			]);

		}


		return $RES->withJSON([
			'data' => [
				'have' => $have,
				'want' => $want,
			],
			'meta' => [
				'note' => 'Not Implemented',
			],
		], 501);

	}

}
