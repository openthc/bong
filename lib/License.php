<?php
/**
 * BONG License
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong;

class License extends \OpenTHC\License
{
	/**
	 * Move everything to stat=100
	 */
	function resetData()
	{
		$sql_args = [
			':l0' => $License['id']
		];

		$c = $this->_dbc->query('UPDATE section SET stat = 100 WHERE stat != 100 AND license_id = :l0', $sql_args);
		echo "Section Reset: $c\n";

		$c = $this->_dbc->query('UPDATE variety SET stat = 100 WHERE stat != 100 AND license_id = :l0', $sql_args);
		echo "Variety Reset: $c\n";

		$c = $this->_dbc->query('UPDATE product SET stat = 100 WHERE stat != 100 AND license_id = :l0', $sql_args);
		echo "Product Reset: $c\n";

		$c = $this->_dbc->query('UPDATE crop SET stat = 100 WHERE stat != 100 AND license_id = :l0', $sql_args);
		echo "Crop Reset: $c\n";

		$c = $this->_dbc->query('UPDATE lot SET stat = 100 WHERE stat != 100 AND license_id = :l0', $sql_args);
		echo "Inventory Reset: $c\n";

		$c = $this->_dbc->query('UPDATE inventory_adjust SET stat = 100 WHERE stat != 100 AND license_id = :l0', $sql_args);
		echo "Inventory-Adjust Reset: $c\n";

		$c = $this->_dbc->query('UPDATE b2b_incoming SET stat = 100 WHERE stat != 100 AND target_license_id = :l0', $sql_args);
		echo "B2B-Incoming Reset: $c\n";

		$c = $this->_dbc->query('UPDATE b2b_outgoing SET stat = 100 WHERE stat != 100 AND source_license_id = :l0', $sql_args);
		echo "B2B-Outgoing Reset: $c\n";

	}

}
