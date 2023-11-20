<?php
/**
 *
 */

namespace OpenTHC\Bong\Traits;

trait GetReturnObject
{
	/**
	 *
	 */
	function getReturnObject($dbc, string $oid) : object
	{
		$sql = <<<SQL
		SELECT * FROM {$this->_tab_name}
		WHERE license_id = :l0
		  AND id = :o0
		SQL;

		$output_data = $dbc->fetchRow($sql, [
			':l0' => $_SESSION['License']['id'],
			':o0' => $oid,
		]);

		$output_data['data'] = json_decode($output_data['data']);

		return (object)$output_data;

	}

}
