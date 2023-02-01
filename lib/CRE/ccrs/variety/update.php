<?php
/**
 * CCRS Update is not Possible
 *
 * SPDX-License-Identifier: MIT
 */


// Check Name
$_POST['name'] = trim($_POST['name']);
if (empty($_POST['name'])) {
	return $RES->withJSON([
		'data' => $_POST,
		'meta' => [
			'note' => 'Missing Variety Name [CVU-015]'
		]
	], 400);
}

$dbc = $REQ->getAttribute('dbc');

// CCRS uses Name as Primary Key, limit of 100 characters
$arg = [
	':v0' => \OpenTHC\CRE\CCRS::sanatize(strtoupper($_POST['name']), 100),
	':l0' => $_SESSION['License']['id'],
	':n0' => $_POST['name'],
	':d0' => json_encode([
		'@version' => 'openthc/2015',
		'@source' => [
			'id' => $ARG['id'],
			'name' => $_POST['name'],
			'type' => $_POST['type'],
		]
	])
];
$arg[':h0'] = \OpenTHC\CRE\Base::objHash([
	'id' => $arg[':v0'],
	'name' => $arg[':n0'],
]);


// UPSERT
$sql = <<<SQL
INSERT INTO variety (id, license_id, name, hash, data)
VALUES (:v0, :l0, :n0, :h0, :d0)
ON CONFLICT (id, license_id) DO
UPDATE SET
	name = :n0
	, hash = :h0
	, stat = 100
	, updated_at = now()
	, data = coalesce(variety.data, '{}'::jsonb) || :d0
WHERE variety.hash != :h0
RETURNING updated_at
SQL;

$ret = $dbc->query($sql, $arg);

// Trigger an upload in the background?
// $cmd = [];
// $cmd[] = sprintf('%s/bin/cre-ccrs.php', APP_ROOT);
// $cmd[] = 'upload-object';
// $cmd[] = sprintf('--license=%s', $_SESSION['License']['id']);
// $cmd[] = sprintf('--object=variety');
// $cmd[] = '2>&1';
// $cmd[] = '&';
// $cmd = implode(' ', $cmd);
// syslog(LOG_DEBUG, $cmd);

// $job_ulid = _ulid();

$R = \OpenTHC\Service\Redis::factory();
// $R->set(sprintf('/job/%s', $job_ulid), json_encode([
// 	'license' => $_SESSION['License']['id'],
// 	'object' => 'variety',
// 	'object-id' => $arg[':v0']
// ]));
// $R->rpush('/cre/ccrs/upload-queue', $job_ulid);
$R->set(sprintf('/license/%s/variety', $_SESSION['License']['id']), 100);

return $RES->withJSON([
	'data' => $ret,
	'meta' => [],
], 200);
