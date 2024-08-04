<?php
/**
 * CannaFax (https://www.cannafax.com/) Integration
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Bong\CRE;

class Cannafax {

	/**
	 *
	 */
	function __construct(?array $cfg=[]) {

		$this->_License = $cfg['License'];
		$this->_auth_token = $cfg['api_bearer_token'];

	}

	/**
	 *
	 */
	function ping() {

		$url = 'https://app.cannafax.com/api/1.1/wf/post_connection_check/';

		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('authorization: Bearer %s', $this->_auth_token),
			'content-type: application/json',
		]);

		curl_setopt($req, CURLOPT_POSTFIELDS, '');

		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$res = json_decode($res);

		return [
			'code' => $inf['http_code'],
			'data' => $res,
			'meta' => [
				'type' => $inf['content_type']
			],
		];

	}

}
