<?php
/**
 * Update Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Update extends \OpenTHC\Controller\Base
{
	protected $_tab_name;

	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		if (empty($this->_tab_name)) {
			__exit_text('Invalid Incantation [CBS-020]', 500);
		}

	}

	/**
	 * Upsert the Record
	 */
	function getUpsertSQL() : string
	{
		// UPSERT
		$sql = <<<SQL
		INSERT INTO {table_name} (id, license_id, name, hash, data)
		VALUES (:o0, :l0, :n0, :h0, :d0)
		ON CONFLICT (id, license_id) DO
		UPDATE SET
			name = :n0
			, hash = :h0
			, stat = 100
			, updated_at = now()
			, data = coalesce({table_name}.data, '{}'::jsonb) || :d0
		WHERE {table_name}.hash != :h0
		RETURNING id, name, updated_at, (hash = :h0) AS hash_match
		SQL;

		$sql = str_replace('{table_name}', $this->_tab_name, $sql);

		return $sql;

	}

	/**
	 * Updates the Redis Status
	 */
	function updateStatus()
	{
		$rdb = \OpenTHC\Service\Redis::factory();
		$rdb->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/stat', $this->_tab_name), 100);
		$rdb->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/stat/time', $this->_tab_name), time());
		$rdb->hset(sprintf('/license/%s', $_SESSION['License']['id']), sprintf('%s/sync', $this->_tab_name), 100);
	}

	/**
	 *
	 */
	function verifyRequest()
	{

	}

}
