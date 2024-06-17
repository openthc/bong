<?php
/**
 *
 */

namespace OpenTHC\Bong\CRE\CCRS;

class Response {

	public $ccrs_datetime;
	public $mail_datetime;

	public $type = ''; // ?? What?

	// Request Object Type
	public $req_type = '';

	// Request ID
	public $req_ulid = '';

	public $res_body = ''; // Response HTML or Text body

	public $res_file = '';

	/**
	 * Parse the source Email and Extract the Goods
	 */
	public function __construct(string $source_mail) {

		// Match Filename
		$message_mime = mailparse_msg_parse_file($source_mail); // resource
		$message_part_list = mailparse_msg_get_structure($message_mime); // Array
		// echo "message-part-list: " . implode(' / ', $message_part_list) . "\n";

		// $mime_part = mailparse_msg_get_part($message_mime, 0); // resource
		$message_head = mailparse_msg_get_part_data($message_mime);

		$this->res_body = mailparse_msg_extract_part_file($message_mime, $source_mail, null);

		$csv_time = '';
		if ( ! empty($message_head['headers']['subject'])) {
			$s = $message_head['headers']['subject'];
			// echo "Subject: {$s}\n";
			if (preg_match('/CCRS Processing Successful/', $s)) {
				$this->type = 'ccrs-success';
				if (preg_match('/file (\w+)_\w+_(\w+)_(\w+)\.csv you submitted has been processed/', $this->res_body, $m)) {
					$this->req_type = $m[1];
					$this->req_ulid = $m[2];
					$csv_time = $m[3];
				}
			} elseif (preg_match('/CCRS errors for file: (\w+)_\w+_(\w+)_(\w+)\.csv/', $s, $m)) {
				$this->type = 'ccrs-failure-data';
				$this->req_type = $m[1];
				$this->req_ulid = $m[2];
				$csv_time = $m[3];
			} elseif (preg_match('/CCRS Processing Error: (\w+)_\w+_(\w+)_(\w+)\.csv/', $s, $m)) {
				$this->type = 'ccrs-failure-full';
				$this->req_type = $m[1];
				$this->req_ulid = $m[2];
				$csv_time = $m[3];
			} elseif (preg_match('/Manifest Generated: (Manifest)_\w+_(\w+)_(\w+)\.csv/', $s, $m)) {
				$this->type = 'b2b-outgoing-manifest';
				$this->req_type = $m[1];
				$this->req_ulid = $m[2];
				$csv_time = $m[3];
			} else {
				throw new \Exception('Cannot Match');
			}
		}

		// from function csv_file_date(string $csv_file) in cre-adapter/lib/CCRS.php which is buggy on TZ
		// https://github.com/openthc/ccrs/issues/69
		if ( ! empty($csv_time)) {
			$dt0 = null;
			$tz0 = new \DateTimeZone('America/Los_Angeles');
			if (strlen($csv_time) <= 15) {
				// $csv_time = $csv_time . '000';
				$dt0 = \DateTime::createFromFormat('Ymd\TGis', $csv_time, $tz0);
			} else {
				$dt0 = \DateTime::createFromFormat('Ymd\TGisv', $csv_time, $tz0);
			}
			if ( ! empty($dt0)) {
				$this->ccrs_datetime = $dt0;
				// var_dump($csv_time);
				// exit;
			}
		}

		// Sometimes their time thing is bullshit
		// https://github.com/openthc/ccrs/issues/44
		$this->mail_datetime = null;
		try {
			$this->mail_datetime = new \DateTime($message_head['headers']['date']);
			// $dt1 = new \DateTime($RES->time, $tz0);
			// var_dump($dt1);
		} catch (\Exception $e) {
			$this->mail_datetime = new \DateTime();
		}

		// $dt1 = clone $dt0;
		// try {
		// 	// Sometimes their time thing is bullshit
		// 	// https://github.com/openthc/ccrs/issues/44
		// 	$dt1 = new \DateTime($res_time, $tz0);
		// 	// var_dump($dt1);
		// } catch (\Exception $e) {
		// 	// Ignore
		// 	$dt1 = new \DateTime($message_head['headers']['date'], $tz0);
		// 	// var_dump($dt1);
		// }


		// Inflate the parts
		$message_part_data = [];
		foreach ($message_part_list as $p) {
			$mime_part = mailparse_msg_get_part($message_mime, $p); // resource
			$mime_part_data = mailparse_msg_get_part_data($mime_part);
			$message_part_data[$p] = $mime_part_data;
			// mailparse_msg_free($mime_part); // nope, doesn't work
		}

		foreach ($message_part_data as $part_key => $part) {

			// echo "$part_key == {$part['content-type']} : {$part['content-name']}\n";
			if ('attachment' != $part['content-disposition']) {
				continue;
			}

			switch ($part['content-type']) {
			case 'application/octet-stream':

				// echo "message: {$message['id']}; part: $part_key is file: {$part['content-name']}\n";
				$part_res = mailparse_msg_get_part($message_mime, $part_key);
				$output_data = mailparse_msg_extract_part_file($part_res, $source_mail, null);
				$this->res_file = sprintf('%s/var/ccrs-incoming/%s', APP_ROOT, $part['content-name']);

				// Match Filename
				// note: Strain response file has unique pattern
				if (preg_match('/^\w+_\w{6,10}_\d+T\d+\.csv$/', $part['content-name'])
					|| preg_match('/^Strain_\d+T\d+\.csv$/', $part['content-name'])) {

					$output_data = $this->csvPatch($output_data);
					$output_size = file_put_contents($this->res_file, $output_data);
					if (0 == $output_size) {
						throw new \Exception('Failed to write Data File');
					}

					break 2; // foreach

				} elseif (preg_match('/^Manifest_(.+)_(\w+)\.pdf$/', $part['content-name'])) {

					// It's the Manifest PDF

					$output_size = file_put_contents($this->res_file, $output_data);
					if (0 == $output_size) {
						throw new \Exception('Failed to write Data File');
					}

					break 2; // foreach

				}

				break;

			default:
				throw new \Exception('Invalid Attachment [CCR-119]');
			}

		}

		mailparse_msg_free($message_mime);

	}

	public function isValid()
	{
		if (! preg_match('/^\w{26}$/', $this->req_ulid)) {
			throw new \Exception('Invalid Response; Missing Request ID [CCR-111]');
		}

		switch ($this->type) {
		case 'b2b-outgoing-manifest':
		case 'ccrs-failure-data':
			if (empty($this->res_file)) {
				throw new \Exception('Invalid Response; Missing Attachment [CCR-138]');
			}
		}

		// if ( ! preg_match('//')) {

		// }
	}

	protected function attachment_extract() {}

	/**
	 * Patch bullshit we find in these files
	 */
	function csvPatch(string $csv_data) : string {

		// Patch the WHOLE BLOB
		// $csv_data = file_get_contents($csv_file);

		// Fix some bullshit they put in the CSVs (Bug #38)
		$csv_data = str_replace('Insert, Update or Delete', 'INSERT UPDATE or DELETE', $csv_data);
		// $part_body = str_replace('Operation is invalid must be Insert,  Update or Delete'
		// 	, 'Operation is invalid must be INSERT UPDATE or DELETE'
		// 	, $part_body);

		// This one always goes "comma space space CheckSum"
		// $part_body = str_replace(',  CheckSum and', ': CheckSum and', $part_body);
		// $part_body = preg_replace('/found, CheckSum/i', 'found: CheckSum', $part_body);

		// words, comma spaces? "Checksum and"
		$csv_data = preg_replace('/(\w+),\s+CheckSum and/', '$1: CheckSum and', $csv_data);

		// file_put_contents($csv_file, $csv_data);
		return $csv_data;

	}

	/**
	 *
	 */
	// function _process_err_list($csv_line)
	function errorExtractFromLine($csv_line) {

		$err_return_list = [];

		$err_source_list = explode(':', $csv_line['ErrorMessage']);
		foreach ($err_source_list as $err_text) {
			$err_text = trim($err_text);
			switch ($err_text) {
			case 'Area is required':
			case 'Area name is over 75 characters':
			case 'CreatedDate must be a date':
			case 'DestinationLicenseeEmailAddress is required':
			case 'DestinationLicenseePhone is required':
			case 'DestinationLicenseePhone must not exceed 14 characters':
			case 'DestinationLicenseNumber must be numeric':
			case 'DestructionDetail is Required if DestructionReason is "Other" and cannot be left blank':
			case 'DestructionMethod is required':
			case 'DestructionReason is required':
			case 'DriverName is required':
			case 'ExternalIdentifier is required':
			case 'FromInventoryExternalIdentifier is required':
			case 'FromLicenseNumber is required':
			case 'FromLicenseNumber must be numeric':
			case 'HarvestDate must be a date':
			case 'InitialQuantity is required':
			case 'InitialQuantity must be numeric':
			case 'Invalid Adjustment Reason':
			case 'Invalid Area':
			case 'Invalid DestinationLicenseeEmailAddress':
			case 'Invalid DestinationLicenseNumber':
			case 'Invalid Destruction Method':
			case 'Invalid Destruction Reason':
			case 'Invalid Details Operation':
			case 'Invalid From LicenseNumber':
			case 'Invalid FromInventoryExternalIdentifier':
			case 'Invalid InventoryCategory/InventoryType combination':
			case 'Invalid InventoryExternalIdentifier':
			case 'Invalid LicenseeID':
			case 'Invalid NumberRecords':
			case 'Invalid OriginLicenseeEmailAddress':
			case 'Invalid OriginLicenseNumber':
			case 'Invalid Plant':
			case 'Invalid Product':
			case 'Invalid Sale':
			case 'Invalid Strain Type':
			case 'Invalid Strain':
			case 'Invalid To InventoryExternalIdentifier':
			case 'Invalid To LicenseNumber':
			case 'Invalid ToInventoryExternalIdentifier':
			case 'Invalid UOM':
			case 'Invalid VehicleColor':
			case 'Invalid VehicleMake':
			case 'Invalid VehiclePlateNumber':
			case 'Invalid VINNumber':
			case 'InventoryCategory is required':
			case 'InventoryExternalIdentifier or PlantExternalIdentifier is required':
			case 'InventoryType is required':
			case 'IsMedical must be True or False':
			case 'LicenseNumber is required':
			case 'LicenseNumber must be numeric':
			case 'Name is over 50 characters': // Variety
			case 'Name is over 75 characters':
			case 'Name is required':
			case 'Operation is invalid must be INSERT UPDATE or DELETE':
			case 'OriginLicenseeEmailAddress is required':
			case 'OriginLicenseePhone is required':
			case 'OriginLicenseePhone must not exceed 14 characters':
			case 'OriginLicenseNumber must be numeric':
			case 'Product is required':
			case 'Quantity is required':
			case 'Quantity must be numeric':
			case 'QuantityOnHand must be numeric':
			case 'SaleDetailExternalIdentifier is required':
			case 'SaleExternalIdentifier is required':
			case 'SoldToLicenseNumber required for wholesale':
			case 'Strain is required':
			case 'Strain Name reported is not linked to the license number. Please ensure the strain being reported belongs to the licensee':
			case 'ToInventoryExternalIdentifier is required':
			case 'ToLicenseNumber is required':
			case 'ToLicenseNumber must be numeric':
			case 'TotalCost must be numeric':
			case 'UnitPrice is required':
			case 'UnitPrice must be numeric':
			case 'UpdatedBy is required for Update or Delete Operations':
			case 'UpdatedDate is required for Update or Delete Operations':
			case 'UpdatedDate must be a date for Update and Delete operations':
			case 'VehicleColor is required':
			case 'VehicleMake is required':
			case 'VehicleModel is required':
			case 'VehiclePlateNumber is required':
			case 'VINNumber is required':
				$err_return_list[] = $err_text;
				break;
			case 'CheckSum and number of records don\'t match':
				// This one is more of a warning but it shows up for every line
				// if the line count NumberRecords field is wrong
				// So, we just ignore it
				break;
			case 'Duplicate External Identifier':
			case 'Duplicate ExternalManifestIdentifier':
			case 'Duplicate Sale for Licensee':
			case 'Duplicate Strain/StrainType':
				// Cool, this generally means everything is OK
				// BUT!! It could mean a conflict of IDs -- like if the object wasn't for the same license?
				// if ('INSERT' == strtoupper($csv_line['Operation'])) {
				// 	$cre_stat = 200;
				// }
				break;
			case 'Duplicate Strain. The Strain must be unique for the LicenseNumber':
				// Special Case on Variety -- Give a 202, not 200
				return [
					'code' => 202,
					'data' => [],
				];
				break;
			case 'Integrator is not authoritzed to update licensee':
			case 'Integrator is not authorized to update licensee':
			case 'OriginLicenseNumber is not assigned to Integrator':
			case 'License Number is not assigned to Integrator':
			case 'LicenseNumber is not assigned to Integrator':
				return [
					'code' => 403,
					'data' => [ $err_text ],
				];
				break;
			case 'ExternalIdentifier not found':
			case 'ExternalManifestIdentifier does not exist in CCRS. Cannot Update or Delete':
			case 'Invalid SaleDetail':
			case 'SaleDetailExternalIdentifier not found':
			case 'SaleExternalIdentifier not found':
				return [
					'code' => 404,
					'data' => [ $err_text ],
				];
				break;
			default:
				var_dump($csv_line);
				echo "Unexpected Error: '$err_text'\nLINE: '{$csv_line['ErrorMessage']}'\n";
				exit(1);
			}
		}

		if (count($err_return_list)) {
			return [
				'code' => 400,
				'data' => $err_return_list
			];
		}

		return [
			'code' => 200,
			'data' => [],
		];

	}

}
