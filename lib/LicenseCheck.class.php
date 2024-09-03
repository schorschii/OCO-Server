<?php

class LicenseCheck {

	/* LEGAL WARNING
	   It is not allowed to modify this file in order to bypass license checks.
	   I decided to not use obfuscation techniques because they suck, so yeah, it's technically possible to bypass the check.
	   Please be so kind and support further development by purchasing licenses from https://georg-sieber.de
	   It depends on you how long this software will be maintained...
	*/

	const FREE_OBJECTS          = 20;
	const DATE_FORMAT           = 'Y-m-d';

	private $currentObjectCount = 0;

	private $licenseIsFree      = false;
	private $licenseValid       = false;
	private $licenseCompany     = '';
	private $licenseObjects     = 0;
	private $licenseExpireTime  = 0;
	private $licenseText        = '';

	function __construct($db) {
		$this->currentObjectCount = count($db->selectAllComputer());
		$this->licenseCompany = LANG('unregistered');

		$licenseJson = $db->settings->get('license');
		$this->licenseValid =
			( $licenseJson===null ? false : $this->checkLicense(json_decode($licenseJson, true)) )
			|| $this->checkFreeLicense();
	}

	public function isValid() : bool {
		return $this->licenseValid;
	}
	public function isFree() : bool {
		return $this->licenseIsFree;
	}
	public function getRemainingTime() : int {
		return $this->licenseExpireTime - time();
	}
	public function getCompany() : string {
		return $this->licenseCompany;
	}
	public function getLicenseText() : string {
		return $this->licenseText;
	}

	private function checkFreeLicense() {
		if($this->currentObjectCount <= self::FREE_OBJECTS) {
			$this->licenseCompany = LANG('unregistered');
			$this->licenseText = str_replace('%1', self::FREE_OBJECTS, LANG('free_license_for'));
			$this->licenseObjects = self::FREE_OBJECTS;
			$this->licenseIsFree = true;
			return true;
		}
	}

	private function checkLicense($licenseContent) {
		if(!$licenseContent
		|| !isset($licenseContent['objects'])
		|| !isset($licenseContent['valid_until'])) {
			$this->licenseText = LANG('invalid_license');
			return false;
		}

		$this->licenseCompany = $licenseContent['company'];
		$this->licenseObjects = intval($licenseContent['objects']);
		$this->licenseExpireTime = $licenseContent['valid_until'];

		$checkStr = $licenseContent['company'].intval($licenseContent['objects']).$licenseContent['valid_until'];
		$checkSum = md5($checkStr);
		$signature = base64_decode($licenseContent['signature']);
		$result = openssl_verify($checkSum, $signature, self::PUBKEY);

		if($result) {
			$timeLicenseExpire = $licenseContent['valid_until'];
			if($timeLicenseExpire > time()) {
				if($this->currentObjectCount <= $this->licenseObjects) {
					$this->licenseText = str_replace('%1', $this->licenseObjects, str_replace('%2', date(self::DATE_FORMAT, $timeLicenseExpire), LANG('license_valid_for_until')));
					return true;
				} else {
					$this->licenseText = str_replace('%1', $this->currentObjectCount, str_replace('%2', $this->licenseObjects, LANG('computer_count_exeeds_license_limit')));
					return false;
				}
			} else {
				$this->licenseText = str_replace('%1', date(self::DATE_FORMAT, $timeLicenseExpire), LANG('license_expired_on'));
				return false;
			}
		} else {
			$this->licenseText = LANG('invalid_license');
			return false;
		}
	}

	const PUBKEY = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4Ec0oyyqe17crn2iV2Pq
+EcJbmILurkKjcEmFl1+oYMbA0qAN1ITmVnUqfW8fB0f2xv+B4PyRIV2WCgA9gms
NpXf9uogEYGD+y2ckkCDPeA9L7mYHyQeVNkH/4Q48XNNKySgYeJ1bGFZ4zs+Yb9U
4/quo9ggmj7jADZNkcYCqRtcHI8cFKWfanugdrVip2dFDxqYRhw1MTIm0bpG99Mr
Ci/WLCHg7FFREb43rEee7pZ/+9sFFagqmxHdj+jnTsw6KYrd+31hOkPaFhrLf1UU
shObls8Ai1LTamE2XaCQRGZyrmg2JkEkvP3oUiQ+okH77HnrV3A2Z/2LqXZAnLgi
VwIDAQAB
-----END PUBLIC KEY-----';

}
