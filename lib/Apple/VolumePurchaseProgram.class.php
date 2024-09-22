<?php

namespace Apple;

class VolumePurchaseProgram {

	const APPLE_VPP_API = 'https://vpp.itunes.apple.com';

	private $db;
	private $appStore;

	function __construct($db) {
		$this->db = $db;
		$this->appStore = new AppStore($db, $this);
	}

	function getToken() {
		$token = $this->db->settings->get('apple-vpp-token');
		if(!$token) throw new \RuntimeException('No VPP token found');
		$token = json_decode($token, true);
		if(!$token) throw new \RuntimeException('Unable to parse token JSON');
		return $token;
	}

	function storeToken(string $b64Token) {
		$tokenJson = base64_decode($b64Token);
		$tokenValue = json_decode($tokenJson, true);
		if(!$tokenValue) throw new \RuntimeException('Unable to parse token JSON');
		$this->db->insertOrUpdateSettingByKey('apple-vpp-token', $tokenJson);
	}

	private function getHeader() {
		return [
			'Authorization: Bearer '.base64_encode(json_encode($this->getToken())),
			'Content-Type: application/json',
			'Accept: application/json',
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
			throw new \Exception('Unexpected status code '.$statusCode);
		curl_close($ch);
		return $response;
	}

	function syncAssets() {
		$assets = $this->getAssets()['assets'];
		foreach($assets as $asset) {
			if(empty($asset['adamId']) || empty($asset['totalCount'])
			|| empty($asset['productType']) || $asset['productType'] != 'App') continue;
			try {
				$metadata = $this->appStore->getAppMetadata($asset['adamId']);
				if(empty($metadata) || empty($metadata['data']))
					throw new \Exception('Unable to get app metadata');
				$app = $metadata['data'][0];
			} catch(Exception $e) {
				echo 'Unable to get app metadata '.$asset['adamId']."\n";
				continue;
			}
			$this->db->insertOrUpdateManagedApp($app['attributes']['name'], $asset['adamId'], $app['attributes']['platformAttributes']['ios']['bundleId'], $asset['totalCount']);
		}
	}

	// https://developer.apple.com/documentation/devicemanagement/client_config
	function clientConfig(string $mdmId, string $mdmName, string $metadata, string $notifyToken, string $notifyUrl) {
		return $this->curlRequest('POST', self::APPLE_VPP_API.'/mdm/v2/client/config', json_encode([
			'mdmInfo' => [
				'id' => $mdmId,
				'name' => $mdmName,
				'metatdata' => $metadata,
			],
			'notificationAuthToken' => $notifyToken,
			'notificationUrl' => $notifyUrl,
			'notificationTypes' => ['ASSET_COUNT','ASSET_MANAGEMENT','USER_MANAGEMENT','USER_ASSOCIATED']
		]));
	}

	// https://developer.apple.com/documentation/devicemanagement/get_assets-o3g
	function getAssets() {
		// TODO: pagination
		$response = $this->curlRequest('GET', self::APPLE_VPP_API.'/mdm/v2/assets', null, 200);
		$values = json_decode($response, true);
		if(!$values) throw new \Exception('Invalid JSON response from server');
		return $values;
	}

	// https://developer.apple.com/documentation/devicemanagement/get_assignments-o3j
	function getAssignments() {
		// TODO: pagination
		$response = $this->curlRequest('GET', self::APPLE_VPP_API.'/mdm/v2/assignments', null, 200);
		$values = json_decode($response, true);
		if(!$values) throw new \Exception('Invalid JSON response from server');
		return $values;
	}

	// https://developer.apple.com/documentation/devicemanagement/associate_assets
	function associateAssets(array $assets, array $clientUserIds, array $serialNumbers) {
		return $this->curlRequest('POST', self::APPLE_VPP_API.'/mdm/v2/assets/associate', json_encode([
			'assets' => $assets,
			'clientUserIds' => $clientUserIds,
			'serialNumbers' => $serialNumbers,
		]), 200);
	}

	// https://developer.apple.com/documentation/devicemanagement/disassociate_assets
	function disassociateAssets(array $assets, array $clientUserIds, array $serialNumbers) {
		return $this->curlRequest('POST', self::APPLE_VPP_API.'/mdm/v2/assets/disassociate', json_encode([
			'assets' => $assets,
			'clientUserIds' => $clientUserIds,
			'serialNumbers' => $serialNumbers,
		]), 200);
	}

	// https://developer.apple.com/documentation/devicemanagement/get_users-o3n
	function getUsers() {
		return $this->curlRequest('GET', self::APPLE_VPP_API.'/mdm/v2/users', null, 200);
	}

}
