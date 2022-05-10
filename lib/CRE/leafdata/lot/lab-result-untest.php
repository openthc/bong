<?php
/**
 * Unlink the Lab Result from an Inventory
 */

	_bill_plan_check();

	$I = new Inventory($_POST['inventory_id']);
	$Im = $I->getMeta();

	$rbe = RBE::factory();

	$mod = array(
		'global_id' => $I['guid'],
		'global_area_id' => $Im['global_area_id'],
		'global_batch_id' => $Im['global_batch_id'],
		'global_strain_id' => $Im['global_strain_id'],
		'global_inventory_type_id' => $Im['global_inventory_type_id'],
		'lab_results_attested' => '0',
		'qty' => floatval($Im['qty']),
		'net_weight' => floatval($Im['net_weight']),
		'medically_compliant' => intval($Im['medically_compliant']),
	);

	$res = $rbe->inventory()->update($mod);
	if ('success' == $res['status']) {

		$I = $rbe->inventory()->sync($I['guid'], 'Lot/Modify/Lab Result/Attest/Drop by User');
		if ($I['id']) {
			// @bug APP-501 Lot-Lab Result rewrite for inventory.qa_thc, inventory.qa_thc
			$I['qa_cbd'] = null;
			$I['qa_thc'] = null;
			$I->save();
		}

		Session::flash('info', 'Lab Result Un-Attested');

	}

	Radix::redirect('/inventory/view?id=' . $_POST['inventory_id']);

