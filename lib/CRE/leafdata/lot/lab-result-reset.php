<?php
/**
 * Reset the Lab Result on Inventory
 */


	$I = new Inventory($_POST['inventory_id']);
	$Im = $I->getMeta();

	$rbe = RBE::factory();

	$mod = array(
		'global_id' => $I['guid'],
		'global_area_id' => $Im['global_area_id'],
		'global_batch_id' => $Im['global_batch_id'],
		'global_strain_id' => $Im['global_strain_id'],
		'global_inventory_type_id' => $Im['global_inventory_type_id'],
		'qty' => floatval($Im['qty']),
		'net_weight' => floatval($Im['net_weight']),
		'is_initial_inventory' => '0',
		'lab_results_attested' => '0',
		'sent_for_testing' => '0',
		'medically_compliant' => strval($Im['medically_compliant']),
		// 'medically_compliant_status' => 'no',
	);

	$res = $rbe->inventory()->update($mod);
	if (200 == $res['code']) {

		$obj = $res['result'];

		$I->delFlag(Inventory::FLAG_QA_FAIL | Inventory::FLAG_QA_MASK | Inventory::FLAG_QA_PASS | Inventory::FLAG_QA_VOID | Inventory::FLAG_QA_WAIT) ;
		$I['meta'] = json_encode($obj);
		$I->save('Lot/Lab_Result/Reset by User');

		if (_is_ajax()) {
			_exit_json([
				'data' => $obj,
				'meta' => [],
			]);
		}

		Session::flash('info', 'Inventory Lab Result Data Reset');
		Radix::redirect('/inventory/view?id=' . $I['id']);

	}

	Session::flash('fail', $rbe->formatError($res));
	Radix::redirect();

