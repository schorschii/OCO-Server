<?php

namespace Apple;

class AppStore {

	const APPLE_STORE_API_ENTERPRISE = 'https://api.ent.apple.com';
	const APPLE_STORE_API_EDUCATION  = 'https://api.ent.apple.com';

	private $apiUrl;
	private $db;
	private $vpp;
	private $jwt;

	function __construct(\DatabaseController $db, VolumePurchaseProgram $vpp, $apiUrl=self::APPLE_STORE_API_ENTERPRISE) {
		$this->db = $db;
		$this->vpp = $vpp;
		$this->apiUrl = $apiUrl;
	}

	function getTeamId() {
		$id = $this->db->settings->get('apple-appstore-teamid');
		if(!$id) throw new \RuntimeException('No App Store team id found');
		return $id;
	}

	function getKeyId() {
		$id = $this->db->settings->get('apple-appstore-keyid');
		if(!$id) throw new \RuntimeException('No App Store key id found');
		return $id;
	}

	function getKey() {
		$key = $this->db->settings->get('apple-appstore-key');
		if(!$key) throw new \RuntimeException('No App Store API key found');
		$key = openssl_pkey_get_private($key, null);
		if(!$key) throw new \RuntimeException('Unable to parse key');
		return $key;
	}

	function storeTeamId(string $teamId) {
		if(strlen($teamId) != 10) throw new \RuntimeException('Must be exactly 10 chars');
		$this->db->insertOrUpdateSettingByKey('apple-appstore-teamid', $teamId);
	}

	function storeKeyId(string $keyId) {
		if(strlen($keyId) != 10) throw new \RuntimeException('Must be exactly 10 chars');
		$this->db->insertOrUpdateSettingByKey('apple-appstore-keyid', $keyId);
	}

	function storeKey(string $keyPem) {
		$key = openssl_pkey_get_private($keyPem, null);
		if(!$key) throw new \RuntimeException('Unable to parse key');
		$this->db->insertOrUpdateSettingByKey('apple-appstore-key', $keyPem);
	}

	private function getHeader() {
		if(empty($this->jwt)) {
			$this->jwt = Util\JsonWebToken::generateJwtEs256($this->getKey(), $this->getKeyId(), $this->getTeamId());
		}
		return [
			'Authorization: Bearer '.$this->jwt,
			'Cookie: itvt='.base64_encode(json_encode($this->vpp->getToken())),
			'User-Agent: Open Computer Orchestration (OCO)',
		];
	}
	private function curlRequest(string $method, string $url, string $payload=null, int $expectedStatusCode=null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
		if($payload) curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($expectedStatusCode && $statusCode !== $expectedStatusCode)
			throw new \Exception('Unexpected App Store API status code '.$statusCode.': '.$response);
		curl_close($ch);
		return $response;
	}

	// https://developer.apple.com/documentation/devicemanagement/app_and_book_management/apps_and_books_for_organizations/generating_developer_tokens
	function getAppMetadata($storeId) {
		$response = $this->curlRequest('GET', $this->apiUrl.'/v1/catalog/us/stoken-authenticated-apps?ids='.urlencode($storeId).'&platform=iphone', null, 200);
		$values = json_decode($response, true);
		if(!$values) throw new \Exception('Invalid JSON response from server');
		return $values;
	}

}
