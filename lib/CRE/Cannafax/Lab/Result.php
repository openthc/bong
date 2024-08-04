<?php
/**
 * CannaFax (https://www.cannafax.com/) Integration
 *
 * Expect the Lab Report is done BEFORE CannaFax
 */

namespace OpenTHC\Bong\CRE\Cannafax\Lab;

class Result {

	protected $_auth_token;

	protected $_License;


	/**
	 *
	 */
	function __construct(?array $cfg=[]) {

		// What?
		$this->_License = $cfg['License'];
		$this->_auth_token = $cfg['api_bearer_token'];

	}

	/**
	 *
	 */
	function getCertificate(array $arg1) : array {

		$arg0 = []; // Big JSON Blob
		$arg2 = array_merge($arg0, $arg1);
		$json = json_encode($arg2);

		$url = 'https://app.cannafax.com/api/1.1/wf/post_images_create_cog/';

		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('authorization: Bearer %s', $this->_auth_token),
			'content-type: application/json',
		]);

		curl_setopt($req, CURLOPT_POSTFIELDS, $json);

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

	/**
	 *
	 */
	function refold_data($src) {

		// Required: Inventory ID, Environemnt, Product, Process, Aroma
		// It's all ICHS

/*
Allowed value - Any number between 0-100

 - 90-100: Aroma is very pungent, detectable from within a few feet.
 - 80-89: Aroma is noticeable from within a few feet.
 - 70-79: Aroma is mild and noticeable only when the container is opened.
 - 60-69: Aroma is not present until after flowers are broken open.
 - 50-59: Aroma is dull or neutral before and after being broken open, but not indicative of defects.
 - <49: The lot smells sour, of mold, fungus, or other defects.
*/


/*
'
*/

	}

}


/*
req: {
	license: {
		id: "SYSTEM_ID",
		code: "",
		name: "",
		country: "",
	}
	inventory: {
		id: "SYSTEM_ID",
		harvest_at: "NULL/ISO8001/RFC3339",
		product: {
			id: "SYSTEM_ID",
			name: "B - Flower",
			note: "",
		},
		variety: {
			id: "SYSTEM_ID",
			name: "",
			type: "Indica",
		},
	},
	lab: {
		sample: {
			id: "SYSTEM_ID",
			collect_at: "ISO8001/RFC3339",
		},
		result: {
			id: "SYSTEM_ID",
			created_at: "ISO8001/RFC3339",
			updated_at: "NULL/ISO8001/RFC3339",
			expires_at: "NULL/ISO8001/RFC3339",
		},
		report: {
			id: "SYSTEM_ID",
			created_at: "",
			updated_at: "",
			expires_at: "",
			link_to_pdf_coa: "",
		},
	}
}
*/

/*
	"Type": inventory.variety.type,
	"Cultivar Name": inventory.variety.name,
	"Harvest Date": inventory.harvest_at,
	"Inventory ID": inventory.id,
	"Environment":"Indoor",
	"Product": inventory.product.name,
	"Process":"Trimmed Dry - Scissors",
	"Description": inventory.product.note,
	"Aroma": lab.sample.aroma, // 55,
	"Aroma Dominant Note": lab.sample.aroma.note.major,
	"Aroma Minor Note": lab.sample.aroma.note.minor,
	"Defects - Mold or Fungus": lab.result[].id.result,
	"Defects - Pest & Pest Bi-product": lab.result[].id.result,
	"Defects - Seeds or Herm": lab.result[].id.result,
	"Defects - Foreign Objects": lab.result[].id.result,
	"Defects - Other":  lab.result[].id.result,
	"Defects - Other Note": lab.result.note,,
	"Lab Report Available": true,
	"Lab - Test Date": lab.result.created_at,
	"Lab - Total Cannabinoids": lab.result[].id.result,
	"Lab - Total THC": lab.result[].id.result,
	"Lab - Total CBD": lab.result[].id.result,
	"Lab - Total Terpenes": lab.result[].id.result,
	"Lab - Water Activity": lab.result[].id.result,
	"Lab - Microbial": lab.result[].id.result,
	"Lab - Mycotoxins": lab.result[].id.result,
	"Lab - Pesticide": lab.result[].id.result,
	"Lab - Heavy Metal": lab.result[].id.result,
	"Extraction Known": true,
	"Extraction Type":"Hash",
	"Extraction Yield": 0.5,
	"Total Quantity": inventory.unit_count,
	"Asking Price": inventory.unit_price, // 1.00,
	"Supplier - Name": license.name,
	"Supplier - State": license.address.state,
	"Supplier - Country": license.address.country,
	"Supplier - Currency": "USD",
	"Supplier - License": license.code,
*/
