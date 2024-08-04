<?php
/**
 * Cannafax Lab Result Certificate Test
 */

namespace OpenTHC\Bong\Test\Cannafax;

class LabResultCertificate_Test extends \OpenTHC\Bong\Test\Base {

	/**
	 * @test
	 */
	function getCertificateViaFile() {

		$arg = $this->makeJSON();
		$arg['closeUpFile']   = file_get_contents(__DIR__ . '/closeUpFile.png.b64');
		$arg['lotSampleFile'] = file_get_contents(__DIR__ . '/closeUpFile.png.b64');
		$arg['bulkFile']      = file_get_contents(__DIR__ . '/closeUpFile.png.b64');

		$api = new \OpenTHC\Bong\CRE\Cannafax\Lab\Result([
			'License' => [],
			'api_bearer_token' => \OpenTHC\Config::get('cre/cannafax/api_bearer_token'),
		]);

		$res = $api->getCertificate($arg);
		$this->assertIsArray($res);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['meta']['type']);
		$this->assertIsObject($res['data']);
		$res = $res['data'];
		$this->assertObjectHasProperty('status', $res);
		$this->assertEquals('success', $res->status);
		$this->assertObjectHasProperty('response', $res);

		$res = $res->response;
		// var_dump($res);
		$this->assertIsObject($res);
		$this->assertObjectHasProperty('cogURL', $res);
		$this->assertObjectHasProperty('cogID', $res);
		$this->assertMatchesRegularExpression('/^http.+cannafax\.com.+CertOfGrade\.pdf$/', $res->cogURL);

	}

	/**
	 *
	 */
	function getCertificateViaLink() {

		$arg = $this->makeJSON();
		// Use PUB?
		// "closeUpFile": file_get_contents(__DIR__ . '/closeUpFile.png.b64')
		// "lotSampleFile": file_get_contents(__DIR__ . '/closeUpFile.png.b64')
		// "bulkFile": file_get_contents(__DIR__ . '/closeUpFile.png.b64')
		// "closeUpFile": "https://cannafaxstorage.blob.core.windows.net/cannafaximages-test/6e143279-7ff0-45f7-ae6c-e9d10ee7271d-closeUpImage.png",
		// "lotSampleFile": "https://cannafaxstorage.blob.core.windows.net/cannafaximages-test/6e143279-7ff0-45f7-ae6c-e9d10ee7271d-lotSampleImage.png",
		// "bulkFile": "https://cannafaxstorage.blob.core.windows.net/cannafaximages-test/6e143279-7ff0-45f7-ae6c-e9d10ee7271d-bulkImage.png"

	}

	function makeJSON() : array {

		$source = <<<TEXT
		{
			"Type":"Indica",
			"Cultivar Name":"Cultivar",
			"Harvest Date": null,
			"Inventory ID":"Inventory ID",
			"Environment":"Indoor",
			"Product":"B - Flower",
			"Process":"Trimmed Dry - Scissors",
			"Description":"Description",
			"Aroma":55,
			"Aroma Dominant Note":"Pine / Woodsy",
			"Aroma Minor Note":"Citrus",
			"Defects - Mold or Fungus":"None Found",
			"Defects - Pest & Pest Bi-product":"Some Found",
			"Defects - Seeds or Herm":"Possible Trace",
			"Defects - Foreign Objects":"None Found",
			"Defects - Other": "Substantial Amount",
			"Defects - Other Note": "Lots of notes",
			"Lab Report Available": true,
			"Lab - Test Date": null,
			"Lab - Total Cannabinoids": 0.5,
			"Lab - Total THC": 0.5,
			"Lab - Total CBD": 0.5,
			"Lab - Total Terpenes": 0.5,
			"Lab - Water Activity": 0.5,
			"Lab - Microbial":"Pass",
			"Lab - Mycotoxins":"Fail",
			"Lab - Pesticide":"Pass",
			"Lab - Heavy Metal":"Pass",
			"Extraction Known": true,
			"Extraction Type":"Hash",
			"Extraction Yield": 0.5,
			"Total Quantity":5000,
			"Asking Price": 1.00,
			"Supplier - Name":"Awesome Growers",
			"Supplier - State": "WA",
			"Supplier - Country": "US",
			"Supplier - License": "LIC123456789",
			"closeUpFile": null,
			"lotSampleFile": null,
			"bulkFile": null
		}
		TEXT;

		$output = json_decode($source, true);
		$output['Harvest Date'] = date(\DateTimeInterface::RFC3339, time() - (86400 * 60));
		$output['Lab - Test Date'] = date(\DateTimeInterface::RFC3339);

		return $output;

	}

}
