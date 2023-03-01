<?php
/**
 * Search
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\Controller\Base;

class Search extends \OpenTHC\Controller\Base
{
	protected $_tab_name;

	/**
	 *
	 */
	function asHTML($res_data)
	{
		// $subC = new \OpenTHC\Controller\Base($this->_container);
		// $data = [];
		// $data['object_list'] = $res_data;
		// $data['column_list'] = [
		// 	'id',
		// 	'inventory_id',
		// 	'name',
		// 	'stat'
		// 	// 'data',
		// 	// 'created_at',
		// 	// 'updated_at',
		// ];
		// $data['column_function'] = [
		// 	'id' => function($val, $rec) { return sprintf('<td><a href="/inventory-adjust/%s">%s</a></td>', $val, $val); },
		// 	'inventory_id' => function($val, $rec) { return sprintf('<td><a href="/inventory/%s">%s</a></td>', $val, $val); },
		// 	// 'name' => function($val, $rec) { return sprintf('<td>%s</td>', __h($val)); },
		// 	// 'data' => function($val, $rec) {
		// 	// 	// $val = json_decode($val, true);
		// 	// 	// return sprintf('<td>%s</td>', json_encode($val['@result']), JSON_PRETTY_PRINT);
		// 	// },
		// ];

		return $this->render('search.php', $res_data);

	}

	/**
	 *
	 */
	function asJSON()
	{

	}

}
