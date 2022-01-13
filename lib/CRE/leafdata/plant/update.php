<?php
/**
	Update the Plant
	@todo Validate the Change Worked, because LeafData replies success even when it fails to update.
*/

// Send to LeafData
$cre = \CRE::factory();

$old = $cre->plant()->one($data['guid']);
$old = RBE_LeafData::de_fuck($old);

// Populate Required Data
$mod = array(
	'global_id' => $old['global_id'],
	'global_strain_id' => $old['global_strain_id'],
	'global_batch_id' => $old['global_batch_id'],
	'plant_created_at' => $old['created_at'],
	'is_mother' => $old['is_mother'],
	'origin' => $old['origin'],
	'stage' => $old['stage'],
);

// Data Data from JSON Request
$mod['global_strain_id'] = $data['variety']['id'];
$mod['is_mother'] = $data['flag']['is_mother'];
// $mod['stage']




// Correct Faulty Data
// Sometimes the OG object as created from LeafData comes with invalid attributes
// So if you go right back to update, it will be wrong
// This catches those cases and corrects the origin field
// invalid values we've seen are: null, 'clones', 'inventory', 'mother', 'multiple', 'none', 'tissue'
switch ($mod['origin']) {
	case 'clone':
	case 'plant':
	case 'seed':
		// OK
		break;
	default:
		$mod['origin'] = 'plant';
		break;
}


$res = $apiP->update($mod);
switch ($res['code']) {
	case 200:
		$obj = $res['result'];

		// Return the Object
		return $RES->withJSON(array(
			'data' => $obj,
		));
		break;
	default:
}

/////////////////////////////////////////

//foreach ($mod as $k => $v) {
//	if ($obj[$k] != $v) {
//		Session::flash('fail', sprintf('LeafData Failed to Update "%s" (%s != %s)', $k, $obj[$k], $v));
//	}
//}

//if ($obj['global_area_id'] != $mod['global_area_id']) {
//
//	$fail++;
//
//	Session::flash('fail', sprintf('LeafData failed to Move Plant: %s', $P['guid']));
//
//	App::log_event('Plant/Move/Fail', array(
//		'text' => sprintf('LeafData failed to Move Plant: %s', $P['guid']),
//		'source_room' => $src['global_area_id'],
//		'target_room' => $R['guid'],
//		'guid' => $P['guid']
//	));
//
