<?php
/**
 * B2B Outgoing Attachment Detail
 *
 * SPDX-License-Identifier: MIT
 */

$dbc = $REQ->getAttribute('dbc');

$sql = <<<SQL
SELECT b2b_outgoing_file.*
	, LENGTH(body) AS filesize
FROM b2b_outgoing_file
JOIN b2b_outgoing ON b2b_outgoing.id = b2b_outgoing_file.id
WHERE (b2b_outgoing.source_license_id = :l0 OR b2b_outgoing.target_license_id = :l0)
  AND b2b_outgoing_file.id = :i0
SQL;
$arg = [
	':l0' => $_SESSION['License']['id'],
	':i0' => $ARG['id'],
];

if (empty($ARG['file_id'])) {
	$ret = $dbc->fetchAll($sql, $arg);
	return $RES->withJSON([
		'data' => _b2b_out_res_to_data($ret),
		'meta' => null,
	], 200);
}
$sql .= ' AND b2b_outgoing_file.id = :f0';
$arg[':f0'] = $ARG['file_id'];

$ret = $dbc->fetchRow($sql, $arg);
if (empty($ret['id'])) {
	return $RES->withJSON([
		'data' => null,
		'meta' => [
			'note' => 'Not Found [BOF-031]',
		],
	], 404);
}

// Content Type / Mime
$mimeType = mime_content_type($ret['body']);
$RES = $RES->withHeader('Content-type', $mimeType);

return $RES->write(stream_get_contents($ret['body']));

function _b2b_out_res_to_data($res)
{
	foreach ($res as &$x) {
		$x['mime'] = mime_content_type($x['body']);
		unset($x['body']);
	}
	return $res;
}