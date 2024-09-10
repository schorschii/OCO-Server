<?php

namespace Apple;

class AutomatedDeviceEnrollment {

	const APPLE_MDMENROLLMENT_API = 'https://mdmenrollment.apple.com';

	const OCO_VENDOR_SIGNING_API  = 'https://apps.sieber.systems/oco-sign-apn-csr.php';

	private $db;

	private $sessionToken;

	function __construct($db) {
		$this->db = $db;
	}

	function getOwnMdmVendorCert() {
		$cert = @openssl_x509_read($this->db->settings->get('apple-mdm-vendor-cert'));
		if(!$cert) throw new \RuntimeException('No own MDM vendor cert found');
		$privkey = @openssl_pkey_get_private($this->db->settings->get('apple-mdm-vendor-key'), null);
		if(!$privkey) throw new \RuntimeException('No own MDM vendor key found');
		return ['cert'=>$cert, 'privkey'=>$privkey];
	}

	function getMdmApnCert() {
		$cert = @openssl_x509_read($this->db->settings->get('apple-mdm-apn-cert'));
		if(!$cert) throw new \RuntimeException('No own MDM APN cert found');
		$privkey = @openssl_pkey_get_private($this->db->settings->get('apple-mdm-apn-key'), null);
		if(!$privkey) throw new \RuntimeException('No own MDM APN key found');
		return ['cert'=>$cert, 'privkey'=>$privkey, 'certinfo'=>openssl_x509_parse($cert)];
	}

	function getMdmApiUrl() {
		$url = $this->db->settings->get('apple-mdm-api-url');
		if(!$url) throw new \RuntimeException('No MDM API URL set');
		return $url;
	}

	function getActivationProfile() {
		$profile = json_decode($this->db->settings->get('apple-mdm-activation-profile'), true);
		if(!$profile) throw new \RuntimeException('Unable to parse profile JSON');
		return $profile;
	}

	function getMdmServerToken() {
		$token = $this->db->settings->get('apple-mdm-token');
		if(!$token) throw new \RuntimeException('No MDM server token found');
		$token = json_decode($token, true);
		if(!$token) throw new \RuntimeException('Unable to parse token JSON');
		return $token;
	}

	function generateMdmVendorCsr() {
		$privKeyPem = $this->db->settings->get('apple-mdm-vendor-key');
		if($privKeyPem) {
			// read existing key for cert renewal
			$privkey = openssl_pkey_get_private($privKeyPem, null);
			if(!$privkey) throw new \RuntimeException('Unable to read existing MDM APN key');
		} else {
			// generate new key and store in DB
			$privkey = openssl_pkey_new(array(
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			));
			openssl_pkey_export($privkey, $pkeyPem, null);
			$this->db->insertOrUpdateSettingByKey('apple-mdm-vendor-key', $pkeyPem);
		}

		// generate CSR
		$dn = array(
			'organizationName' => 'Brain Limited',
			'commonName' => 'oco.sieber.systems',
		);
		$csr = openssl_csr_new($dn, $privkey, array('digest_alg' => 'sha256'));
		openssl_csr_export($csr, $csrPem);
		return $csrPem;
	}

	function generateMdmApnCsr() {
		$privKeyPem = $this->db->settings->get('apple-mdm-apn-key');
		if($privKeyPem) {
			// read existing key for cert renewal
			$privkey = openssl_pkey_get_private($privKeyPem, null);
			if(!$privkey) throw new \RuntimeException('Unable to read existing MDM APN key');
		} else {
			// generate new key and store in DB
			$privkey = openssl_pkey_new(array(
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			));
			openssl_pkey_export($privkey, $pkeyPem, null);
			$this->db->insertOrUpdateSettingByKey('apple-mdm-apn-key', $pkeyPem);
		}

		// generate customer CSR
		$dn = array(
			'organizationName' => 'Brain Limited',
			'commonName' => 'oco.sieber.systems',
		);
		$csr = openssl_csr_new($dn, $privkey, array('digest_alg' => 'sha256'));
		openssl_csr_export($csr, $csrPem);
		$csrDer = self::pem2der($csrPem);
		$csrDerB64 = base64_encode($csrDer);

		// sign the customer CSR with MDM vendor cert
		try {
			$mdmVendorCert = $this->getOwnMdmVendorCert();
			openssl_sign($csrDer, $csrDerSig, $mdmVendorCert['privkey'], 'sha256WithRSAEncryption');

			$csrDerSigB64 = base64_encode($csrDerSig);
			openssl_x509_export($mdmVendorCert['cert'], $mdmVendorCertPem);
			$certChain = trim($mdmVendorCertPem).self::APPLE_CERT_CHAIN;
		} catch(\RuntimeException $e) {
			$license = new \LicenseCheck($this->db);
			if($license->isValid()) {
				$sigResult = $this->signMdmApnCsrWithOcoVendorCert($csrDerB64, $license->getLicenseJson());
				$csrDerSigB64 = $sigResult['signature'];
				$certChain = $sigResult['cert-chain'];
			} else {
				throw new \RuntimeException('No own MDM vendor cert set and no valid OCO license - cannot generate APN CSR');
			}
		}

		// create CSR plist
		$td = new \CFPropertyList\CFTypeDetector();
		$plist = new \CFPropertyList\CFPropertyList();
		$plist->add( $td->toCFType( [
			'PushCertRequestCSR' => $csrDerB64,
			'PushCertCertificateChain' => $certChain,
			'PushCertSignature' => $csrDerSigB64,
		] ) );
		return base64_encode($plist->toXML(true));
	}

	private function signMdmApnCsrWithOcoVendorCert(string $apnCsr, string $ocoLicense) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::OCO_VENDOR_SIGNING_API);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
			'apn-csr' => $apnCsr,
			'oco-license' => $ocoLicense,
		)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$resultArray = json_decode($response, true);
		if(!$resultArray || empty($resultArray['signature']) || empty($resultArray['cert-chain'])) {
			throw new \RuntimeException('Invalid response from OCO vendor APN CSR signing API');
		}
		curl_close($ch);
		return $resultArray;
	}

	function generateMdmServerTokenCert() {
		$certPem = $this->db->settings->get('apple-mdm-token-cert');
		if($certPem) {
			// todo: check if valid PEM
			return $certPem;
		}

		$privKeyPem = $this->db->settings->get('apple-mdm-token-key');
		if($privKeyPem) {
			// read existing key for cert renewal
			$privKey = openssl_pkey_get_private($privKeyPem, null);
			if(!$privKey) throw new \RuntimeException('Unable to read existing MDM Token key');
		} else {
			// generate new key and store in DB
			$privKey = openssl_pkey_new(array(
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			));
			openssl_pkey_export($privKey, $privKeyPem, null);
			$this->db->insertOrUpdateSettingByKey('apple-mdm-token-key', $privKeyPem);
		}

		$dn = array(
			'organizationName' => 'Sieber Systems',
			'commonName' => 'sandbox.sieber.systems',
		);
		$csr = openssl_csr_new($dn, $privKey, array('digest_alg' => 'sha256'));

		$cert = openssl_csr_sign($csr, null, $privKey, 36500);
		openssl_x509_export($cert, $certPem);
		$this->db->insertOrUpdateSettingByKey('apple-mdm-token-cert', $certPem);

		return $certPem;
	}

	/*private*/ function getMdmDeviceCa() {
		$certPem = $this->db->settings->get('apple-mdm-deviceca-cert');
		$privKeyPem = $this->db->settings->get('apple-mdm-deviceca-key');
		if($certPem && $privKeyPem) {
			// todo: check if valid PEM
			return ['cert'=>$certPem, 'privkey'=>$privKeyPem];
		}

		// generate new key and store in DB
		$privKey = openssl_pkey_new(array(
			'private_key_bits' => 4096,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		));
		openssl_pkey_export($privKey, $privKeyPem, null);
		$this->db->insertOrUpdateSettingByKey('apple-mdm-deviceca-key', $privKeyPem);

		$dn = array(
			'organizationName' => 'Sieber Systems',
			'commonName' => 'sandbox.sieber.systems',
		);
		$csr = openssl_csr_new($dn, $privKey, array('digest_alg' => 'sha256'));

		$cert = openssl_csr_sign($csr, null, $privKey, 36500);
		openssl_x509_export($cert, $certPem);
		$this->db->insertOrUpdateSettingByKey('apple-mdm-deviceca-cert', $certPem);

		return ['cert'=>$certPem, 'privkey'=>$privKeyPem];
	}
	function getMdmDeviceCaCert() {
		return $this->getMdmDeviceCa()['cert'];
	}

	function storeMdmServerToken($p7mContainer) {
		$certPem = $this->db->settings->get('apple-mdm-token-cert');
		if(!$certPem) {
			throw new \RuntimeException('MDM Token cert not found or invalid');
		}
		$keyPem = $this->db->settings->get('apple-mdm-token-key');
		if(!$keyPem) {
			throw new \RuntimeException('MDM Token key not found or invalid');
		}

		$tmpInFile  = '/tmp/mdm_token_encrypted.txt';
		$tmpOutFile = '/tmp/mdm_token_decrypted.txt';
		file_put_contents($tmpInFile, $p7mContainer);
		openssl_cms_decrypt(
			$tmpInFile,
			$tmpOutFile,
			$certPem,
			$keyPem,
			OPENSSL_ENCODING_SMIME
		);

		$mimeMessage = file_get_contents($tmpOutFile);
		$matched = preg_match('/(?<=-----BEGIN MESSAGE-----\n)[\s\S]*(?=\n-----END MESSAGE-----)/', $mimeMessage, $matches);
		if(count($matches) !== 1) {
			throw new \RuntimeException('Unexpected number of regex matches: '.count($matches));
		}
		$jsonMessage = $matches[0];
		if(!json_encode($jsonMessage)) {
			throw new \RuntimeException('Decrypted message it no a valid JSON');
		}
		$this->db->insertOrUpdateSettingByKey('apple-mdm-token', $jsonMessage);
	}

	private function getHeader() {
		if(empty($this->sessionToken)) {
			$this->aquireSessionToken();
		}
		return [
			'X-ADM-Auth-Session: '.$this->sessionToken,
			'X-Server-Protocol-Version: 3',
			'Content-Type: application/json;charset=UTF8',
			'User-Agent: OCO',
		];
	}
	function aquireSessionToken() {
		$token = $this->getMdmServerToken();
		$oauth = new \OAuth($token['consumer_key'], $token['consumer_secret']);
		$oauth->setToken($token['access_token'], $token['access_secret']);

		$sessionUrl = self::APPLE_MDMENROLLMENT_API.'/session';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $sessionUrl);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: '.$oauth->getRequestHeader('GET', $sessionUrl)
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$responseJson = json_decode($response, true);
		if(!$responseJson || empty($responseJson['auth_session_token'])) {
			throw new \RuntimeException('Invalid response from ABM/ASM');
		}

		$this->sessionToken = $responseJson['auth_session_token'];
	}
	private function curlRequest(string $method, string $url, string $payload=null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
		if($payload) curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	// https://developer.apple.com/documentation/devicemanagement/get_a_list_of_devices
	function syncDevices() {
		$response = $this->curlRequest('POST', self::APPLE_MDMENROLLMENT_API.'/server/devices');
		$result = json_decode($response, true);
		if(!$result || !isset($result['devices']) || !is_array($result['devices']))
			throw new \RuntimeException('Invalid response from server');

		$devicesWithoutProfile = [];
		foreach($result['devices'] as $device) {
			if(empty($device['serial_number'])) continue;
			$md = $this->db->selectMobileDeviceBySerialNumber($device['serial_number']);
			if($md) {
				$this->db->updateMobileDevice($md->id,
					$md->udid, $md->device_name, $md->serial, $device['description']??'',
					$device['model']??'', $device['os']??'', $device['device_family']??'', $device['color']??'',
					$device['profile_uuid']??null, $md->push_token, $md->push_magic, $md->push_sent,
					$md->unlock_token, $md->info, $md->notes, $md->force_update
				);
			} else {
				echo 'Creating device '.$device['serial_number'].'...'."\n";
				$this->db->insertMobileDevice(
					null/*udid*/, ''/*name*/, $device['serial_number'], $device['description']??'',
					$device['model']??'', $device['os']??'', $device['device_family']??'', $device['color']??'',
					$device['profile_uuid']??null, null/*push_token*/, null/*push_magic*/, null/*push_sent*/,
					null/*unlock_token*/, null/*info*/, ''/*notes*/, 0/*force_update*/
				);
			}
			if(empty($device['profile_uuid'])) {
				$devicesWithoutProfile[] = $device['serial_number'];
			}
		}
		// assign the activation profile to devices if empty
		// todo: update if changed
		if(!empty($devicesWithoutProfile)) {
			echo 'Assigning activation profile to '.count($devicesWithoutProfile).' device(s)...'."\n";
			$createResult = $this->createProfile(json_encode($this->getActivationProfile()));
			$profileUuid = json_decode($createResult,true)['profile_uuid'];
			$this->assignProfile($profileUuid, $devicesWithoutProfile);
			// todo: update profile_uuid in database
		}
	}

	// https://developer.apple.com/documentation/devicemanagement/get_device_details
	function getDeviceInfo(array $devices) {
		return $this->curlRequest('POST', self::APPLE_MDMENROLLMENT_API.'/devices', json_encode(['devices' => $devices]));
	}

	// https://developer.apple.com/documentation/devicemanagement/define_a_profile
	function createProfile(string $profile_json) {
		return $this->curlRequest('POST', self::APPLE_MDMENROLLMENT_API.'/profile', $profile_json);
	}

	// https://developer.apple.com/documentation/devicemanagement/get_a_profile
	function getProfile(string $uuid) {
		return $this->curlRequest('GET', self::APPLE_MDMENROLLMENT_API.'/profile'.'?'.http_build_query(['profile_uuid'=>$uuid]));
	}

	// https://developer.apple.com/documentation/devicemanagement/assign_a_profile
	function assignProfile(string $uuid, array $devices) {
		return $this->curlRequest('POST', self::APPLE_MDMENROLLMENT_API.'/profile/devices', json_encode(['profile_uuid'=>$uuid, 'devices'=>$devices]));
	}

	// https://developer.apple.com/documentation/devicemanagement/remove_a_profile-c2c
	function deleteProfile(string $uuid, array $devices) {
		return $this->curlRequest('DELETE', self::APPLE_MDMENROLLMENT_API.'/profile/devices', json_encode(['profile_uuid'=>$uuid, 'devices'=>$devices]));
	}

	function generateEnrollmentProfile(string $serial) {
		$ca = $this->getMdmDeviceCa();

		// create a new device cert, signed by internal CA
		$passphrase = randomString();
		$privKey = openssl_pkey_new([
			'private_key_bits'  => 4096,
			'private_key_type'  => OPENSSL_KEYTYPE_RSA,
		]);
		$csr = openssl_csr_new([
			'organizationName' => 'Sieber Systems',
			'commonName' => $serial,
		], $privKey);
		$cert = openssl_csr_sign($csr, $ca['cert'], $ca['privkey'], 3650, null, 0);

		/*
		   So, here we have a sad hack. Current OpenSSL versions create pkcs12 containers with:
		   MAC: sha256, Iteration 2048, length: 32, salt length: 8
		   PKCS7 Encrypted data: PBES2, PBKDF2, AES-256-CBC, Iteration 2048, PRF hmacWithSHA256

		   But this format is not compatible with many clients, including iOS. So we need to
		   create the pkcs12 with "-legacy" OpenSSL parameter, generating a .p12 file with:
		   MAC: sha1, Iteration 2048, length: 20, salt length: 8
		   PKCS7 Encrypted data: pbeWithSHA1And40BitRC2-CBC, Iteration 2048

		   But using the "-legacy" parameter seems not possible with PHP's
		   openssl_pkcs12_export($cert, $pkcs12data, $privKey, $passphrase) function.
		   That's why, we need to call the openssl command line tool.
		   Don't do this at home, kids!
		*/
		$tmpDeviceKey = '/tmp/device.key';
		$tmpDeviceCrt = '/tmp/device.crt';
		$tmpDeviceP12 = '/tmp/device.p12';
		openssl_pkey_export_to_file($privKey, $tmpDeviceKey, null);
		openssl_x509_export_to_file($cert, $tmpDeviceCrt);
		if(system('/usr/bin/openssl pkcs12 -export -inkey '.$tmpDeviceKey.' -in '.$tmpDeviceCrt.' -out '.$tmpDeviceP12.' -legacy -password pass:'.$passphrase, $returnCode) === false) {
			throw new \RuntimeException('Unable to run openssl command');
		}
		if($returnCode !== 0) {
			throw new \RuntimeException('openssl command return code is '.$returnCode);
		}
		$pkcs12data = file_get_contents($tmpDeviceP12);

		// create profile plist
		$td = new \CFPropertyList\CFTypeDetector();
		$plist = new \CFPropertyList\CFPropertyList();
		$plist->add( $td->toCFType( [
			'PayloadDisplayName' => 'OCO MDM (yay!)',
			'PayloadIdentifier' => 'systems.sieber.oco.enrollment',
			'PayloadType' => 'Configuration',
			'PayloadUUID' => '1f4ef23b-ab01-45b9-879c-7a036e47b083',
			'PayloadVersion' => 1,
			'PayloadContent' => [
				[ # https://developer.apple.com/documentation/devicemanagement/certificatepkcs12
					'PayloadIdentifier' => 'systems.sieber.oco.device_cert_pkcs12_payload',
					'PayloadType' => 'com.apple.security.pkcs12',
					'PayloadUUID' => '47492623-e4e7-4a64-ba63-2f31d2ca1a5f',
					'PayloadVersion' => 1,
					'PayloadContent' => new \CFPropertyList\CFData($pkcs12data),
					'Password' => $passphrase,
					'PayloadCertificateFileName' => 'device_identity.p12'
				],
				[ # https://developer.apple.com/documentation/devicemanagement/mdm
					'PayloadIdentifier' => 'systems.sieber.oco.mdm_payload',
					'PayloadType' => 'com.apple.mdm',
					'PayloadUUID' => '0ae4af50-590a-4478-b540-aa0a21da23f1',
					'PayloadVersion' => 1,
					'IdentityCertificateUUID' => '47492623-e4e7-4a64-ba63-2f31d2ca1a5f',
					'ServerURL' => $this->getMdmApiUrl().'/mdm',
					'Topic' => $this->getMdmApnCert()['certinfo']['subject']['UID'],
					'AccessRights' => 8191,
					'ServerCapabilities' => [],
					'CheckInURL' => $this->getMdmApiUrl().'/checkin',
					'CheckOutWhenRemoved' => true,
					'SignMessage' => true,
					#'PromptUserToAllowBootstrapTokenForAuthentication' => false,
				]
			]
		] ) );
		return $plist->toXML(true);
	}

	static function pem2der($pem_data) {
		$pem_data = trim(preg_replace('/-----(.*)-----/', '', $pem_data));
		$der = base64_decode($pem_data);
		return $der;
	}
	static function der2pem($der_data, $type='CERTIFICATE') {
		$pem = chunk_split(base64_encode($der_data), 64, "\n");
		$pem = "-----BEGIN ".$type."-----\n".$pem."-----END ".$type."-----\n";
		return $pem;
	}

	const APPLE_CERT_CHAIN = '-----BEGIN CERTIFICATE-----'
		.'MIIEUTCCAzmgAwIBAgIQfK9pCiW3Of57m0R6wXjF7jANBgkqhkiG9w0BAQsFADBi'
		.'MQswCQYDVQQGEwJVUzETMBEGA1UEChMKQXBwbGUgSW5jLjEmMCQGA1UECxMdQXBw'
		.'bGUgQ2VydGlmaWNhdGlvbiBBdXRob3JpdHkxFjAUBgNVBAMTDUFwcGxlIFJvb3Qg'
		.'Q0EwHhcNMjAwMjE5MTgxMzQ3WhcNMzAwMjIwMDAwMDAwWjB1MUQwQgYDVQQDDDtB'
		.'cHBsZSBXb3JsZHdpZGUgRGV2ZWxvcGVyIFJlbGF0aW9ucyBDZXJ0aWZpY2F0aW9u'
		.'IEF1dGhvcml0eTELMAkGA1UECwwCRzMxEzARBgNVBAoMCkFwcGxlIEluYy4xCzAJ'
		.'BgNVBAYTAlVTMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2PWJ/KhZ'
		.'C4fHTJEuLVaQ03gdpDDppUjvC0O/LYT7JF1FG+XrWTYSXFRknmxiLbTGl8rMPPbW'
		.'BpH85QKmHGq0edVny6zpPwcR4YS8Rx1mjjmi6LRJ7TrS4RBgeo6TjMrA2gzAg9Dj'
		.'+ZHWp4zIwXPirkbRYp2SqJBgN31ols2N4Pyb+ni743uvLRfdW/6AWSN1F7gSwe0b'
		.'5TTO/iK1nkmw5VW/j4SiPKi6xYaVFuQAyZ8D0MyzOhZ71gVcnetHrg21LYwOaU1A'
		.'0EtMOwSejSGxrC5DVDDOwYqGlJhL32oNP/77HK6XF8J4CjDgXx9UO0m3JQAaN4LS'
		.'VpelUkl8YDib7wIDAQABo4HvMIHsMBIGA1UdEwEB/wQIMAYBAf8CAQAwHwYDVR0j'
		.'BBgwFoAUK9BpR5R2Cf70a40uQKb3R01/CF4wRAYIKwYBBQUHAQEEODA2MDQGCCsG'
		.'AQUFBzABhihodHRwOi8vb2NzcC5hcHBsZS5jb20vb2NzcDAzLWFwcGxlcm9vdGNh'
		.'MC4GA1UdHwQnMCUwI6AhoB+GHWh0dHA6Ly9jcmwuYXBwbGUuY29tL3Jvb3QuY3Js'
		.'MB0GA1UdDgQWBBQJ/sAVkPmvZAqSErkmKGMMl+ynsjAOBgNVHQ8BAf8EBAMCAQYw'
		.'EAYKKoZIhvdjZAYCAQQCBQAwDQYJKoZIhvcNAQELBQADggEBAK1lE+j24IF3RAJH'
		.'Qr5fpTkg6mKp/cWQyXMT1Z6b0KoPjY3L7QHPbChAW8dVJEH4/M/BtSPp3Ozxb8qA'
		.'HXfCxGFJJWevD8o5Ja3T43rMMygNDi6hV0Bz+uZcrgZRKe3jhQxPYdwyFot30ETK'
		.'XXIDMUacrptAGvr04NM++i+MZp+XxFRZ79JI9AeZSWBZGcfdlNHAwWx/eCHvDOs7'
		.'bJmCS1JgOLU5gm3sUjFTvg+RTElJdI+mUcuER04ddSduvfnSXPN/wmwLCTbiZOTC'
		.'NwMUGdXqapSqqdv+9poIZ4vvK7iqF0mDr8/LvOnP6pVxsLRFoszlh6oKw0E6eVza'
		.'UDSdlTs='
		.'-----END CERTIFICATE-----'
		.'-----BEGIN CERTIFICATE-----'
		.'MIIEuzCCA6OgAwIBAgIBAjANBgkqhkiG9w0BAQUFADBiMQswCQYDVQQGEwJVUzET'
		.'MBEGA1UEChMKQXBwbGUgSW5jLjEmMCQGA1UECxMdQXBwbGUgQ2VydGlmaWNhdGlv'
		.'biBBdXRob3JpdHkxFjAUBgNVBAMTDUFwcGxlIFJvb3QgQ0EwHhcNMDYwNDI1MjE0'
		.'MDM2WhcNMzUwMjA5MjE0MDM2WjBiMQswCQYDVQQGEwJVUzETMBEGA1UEChMKQXBw'
		.'bGUgSW5jLjEmMCQGA1UECxMdQXBwbGUgQ2VydGlmaWNhdGlvbiBBdXRob3JpdHkx'
		.'FjAUBgNVBAMTDUFwcGxlIFJvb3QgQ0EwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAw'
		.'ggEKAoIBAQDkkakJH5HbHkdQ6wXtXnmELes2oldMVeyLGYne+Uts9QerIjAC6Bg+'
		.'+FAJ039BqJj50cpmnCRrEdCju+QbKsMflZ56DKRHi1vUFjczy8QPTc4UadHJGXL1'
		.'XQ7Vf1+b8iUDulWPTV0N8WQ1IxVLFVkds5T39pyez1C6wVhQZ48ItCD3y6wsIG9w'
		.'tj8BMIy3Q88PnT3zK0koGsj+zrW5DtleHNbLPbU6rfQPDgCSC7EhFi501TwN22IW'
		.'q6NxkkdTVcGvL0Gz+PvjcM3mo0xFfh9Ma1CWQYnEdGILEINBhzOKgbEwWOxaBDKM'
		.'aLOPHd5lc/9nXmW8Sdh2nzMUZaF3lMktAgMBAAGjggF6MIIBdjAOBgNVHQ8BAf8E'
		.'BAMCAQYwDwYDVR0TAQH/BAUwAwEB/zAdBgNVHQ4EFgQUK9BpR5R2Cf70a40uQKb3'
		.'R01/CF4wHwYDVR0jBBgwFoAUK9BpR5R2Cf70a40uQKb3R01/CF4wggERBgNVHSAE'
		.'ggEIMIIBBDCCAQAGCSqGSIb3Y2QFATCB8jAqBggrBgEFBQcCARYeaHR0cHM6Ly93'
		.'d3cuYXBwbGUuY29tL2FwcGxlY2EvMIHDBggrBgEFBQcCAjCBthqBs1JlbGlhbmNl'
		.'IG9uIHRoaXMgY2VydGlmaWNhdGUgYnkgYW55IHBhcnR5IGFzc3VtZXMgYWNjZXB0'
		.'YW5jZSBvZiB0aGUgdGhlbiBhcHBsaWNhYmxlIHN0YW5kYXJkIHRlcm1zIGFuZCBj'
		.'b25kaXRpb25zIG9mIHVzZSwgY2VydGlmaWNhdGUgcG9saWN5IGFuZCBjZXJ0aWZp'
		.'Y2F0aW9uIHByYWN0aWNlIHN0YXRlbWVudHMuMA0GCSqGSIb3DQEBBQUAA4IBAQBc'
		.'NplMLXi37Yyb3PN3m/J20ncwT8EfhYOFG5k9RzfyqZtAjizUsZAS2L70c5vu0mQP'
		.'y3lPNNiiPvl4/2vIB+x9OYOLUyDTOMSxv5pPCmv/K/xZpwUJfBdAVhEedNO3iyM7'
		.'R6PVbyTi69G3cN8PReEnyvFteO3ntRcXqNx+IjXKJdXZD9Zr1KIkIxH3oayPc4Fg'
		.'xhtbCS+SsvhESPBgOJ4V9T0mZyCKM2r3DYLP3uujL/lTaltkwGMzd/c6ByxW69oP'
		.'IQ7aunMZT7XZNn/Bh1XZp5m5MkL72NVxnn6hUrcbvZNCJBIqxw8dtk2cXmPIS4AX'
		.'UKqK1drk/NAJBzewdXUh'
		.'-----END CERTIFICATE-----';

}
