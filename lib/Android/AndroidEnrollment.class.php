<?php

namespace Android;

use \Apple\Util\JsonWebToken as JsonWebToken;

class AndroidEnrollment
{

	const ANDROID_MANAGEMENT_API_URL = 'https://androidmanagement.googleapis.com/v1';

	const DEFAULT_POLICIES = [
		'statusReportingSettings' => [
			'applicationReportsEnabled' => true,
			'deviceSettingsEnabled' => true,
			'softwareInfoEnabled' => true,
			'memoryInfoEnabled' => true,
			'networkInfoEnabled' => true,
			'displayInfoEnabled' => true,
			'powerManagementEventsEnabled' => true,
			'hardwareStatusEnabled' => true,
			'systemPropertiesEnabled' => true,
			'applicationReportingSettings' => [
				'includeRemovedApps' => true,
			],
			'commonCriteriaModeEnabled' => true
		]
	];

	private $db;
	private $accessToken;

	function __construct(\DatabaseController $db)
	{
		$this->db = $db;
	}

	// this function is for enrollment with a custom DPC admin app (not Android Enterprise API)
	// (currently not used)
	const TMP_QR_FILE  = '/tmp/qr.png';
	const TMP_APK_FILE = '/tmp/mdm.apk';
	function generateEnrollmentQrCode(string $downloadLocation, string $componentName, array $extra = null)
	{
		$file = fopen($downloadLocation, 'r');
		$apkContent = stream_get_contents($file);
		return self::generateQrCode(json_encode([
			'android.app.extra.PROVISIONING_DEVICE_ADMIN_COMPONENT_NAME' => $componentName,
			'android.app.extra.PROVISIONING_DEVICE_ADMIN_PACKAGE_DOWNLOAD_LOCATION' => $downloadLocation,
			'android.app.extra.PROVISIONING_DEVICE_ADMIN_PACKAGE_CHECKSUM' => JsonWebToken::base64UrlEncode(hash('sha256', $apkContent, true)),
			'android.app.extra.PROVISIONING_ADMIN_EXTRAS_BUNDLE' => $extra,
		]));
	}
	static function generateQrCode(string $content)
	{
		$qrGenerator = new \QRCode($content);
		$qrImage = $qrGenerator->render_image();
		imagepng($qrImage, self::TMP_QR_FILE);
		return file_get_contents(self::TMP_QR_FILE);
	}

	function getMdmApiUrl()
	{
		$url = $this->db->settings->get('apple-mdm-api-url');
		if (!$url) throw new \RuntimeException('No MDM API URL set');
		return $url;
	}

	function getOAuthCredentials()
	{
		$cred = $this->db->settings->get('google-api-credentials');
		if (!$cred) throw new \RuntimeException('No Google API credentials found');
		$cred = json_decode($cred, true);
		if (!$cred) throw new \RuntimeException('Unable to parse credentials JSON');
		if (empty($cred['client_email']) || empty($cred['project_id']) || empty($cred['private_key']))
			throw new \RuntimeException('"client_email", "project_id" or "private_key" missing!');
		return $cred;
	}
	private function aquireAccessToken()
	{
		/*$oauth = new \Google_Client();
		$oauth->setApplicationName(LANG('app_name'));
		$oauth->setAuthConfig($this->getOAuthCredentials(), true);
		$oauth->setScopes(['https://www.googleapis.com/auth/androidmanagement']);
		$oauth->setAccessToken(json_decode($token, true));
		if($oauth->isAccessTokenExpired()) {
			$oauth->refreshTokenWithAssertion();
		}
		$token = $oauth->getAccessToken();*/

		// restore cached token
		$tokenJson = $this->db->settings->get('google-api-token');
		if ($tokenJson) {
			$token = json_decode($tokenJson, true);
			if (
				empty($token['access_token'])
				|| empty($token['expires_in'])
				|| empty($token['created'])
				|| $token['created'] + $token['expires_in'] < time()
			) {
				// renew token if invalid or expired
				$token = $this->getAccessToken();
			}
		} else {
			$token = $this->getAccessToken();
		}
		// cache the access token
		$this->db->insertOrUpdateSettingByKey('google-api-token', json_encode($token));
		// return the tokem value
		return $token['access_token'];
	}
	private function getAccessToken()
	{
		$creds = $this->getOAuthCredentials();
		$token = $this->apiCall('POST', 'https://www.googleapis.com/oauth2/v4/token', http_build_query([
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion' => JsonWebToken::generateJwt(
				'RS256',
				$creds['private_key'],
				$creds['private_key_id'],
				$creds['client_email'],
				'https://www.googleapis.com/oauth2/v4/token',
				'https://www.googleapis.com/auth/androidmanagement'
			)
		]), 200, []);
		if (
			empty($token['access_token'])
			|| empty($token['expires_in'])
		)
			throw new \Exception('Unexpected response from Google OAuth API');
		$token['created'] = time();
		return $token;
	}
	/*private*/
	function apiCall($method, $url, $body = null, $expectedStatusCode = 200, $header = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		#curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			$header === null ? [
				'Authorization: Bearer ' . $this->aquireAccessToken(),
				'Content-Type: application/json',
			] : $header
		);
		$response = curl_exec($ch);

		// Get status code BEFORE closing the handle
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// Now it's safe to close the handle
		curl_close($ch);

		if ($expectedStatusCode && $statusCode !== $expectedStatusCode)
			throw new \Exception('Unexpected status code ' . $statusCode . ' ' . $response);

		return json_decode($response, true);
	}

	function generateSignupUrl()
	{
		$response = $this->apiCall('POST', self::ANDROID_MANAGEMENT_API_URL . '/signupUrls', json_encode([
			'projectId' => $this->getOAuthCredentials()['project_id'],
			'callbackUrl' => $this->getMdmApiUrl(),
			#'adminEmail' => 'mdm@example.com',
		]));
		if (empty($response['name']) || empty($response['url']))
			throw new \RuntimeException('Unexpected server response: ' . json_encode($response));
		$this->db->insertOrUpdateSettingByKey('google-signup-url', json_encode($response));
	}
	function getSignupUrl()
	{
		$data = $this->db->settings->get('google-signup-url');
		if (!$data) throw new \RuntimeException('No signup URL found');
		$data = json_decode($data, true);
		if (!$data) throw new \RuntimeException('Unable to parse JSON');
		if (empty($data['url']) || empty($data['name']))
			throw new \RuntimeException('"url" or "name" missing!');
		return $data;
	}

	function getCompanyName()
	{
		$data = $this->db->settings->get('google-company-name');
		if (!$data) throw new \RuntimeException('No comapny name set');
		return $data;
	}
	function createEnterprise(string $enterpriseToken)
	{
		$response = $this->apiCall('POST', self::ANDROID_MANAGEMENT_API_URL . '/enterprises?' . http_build_query([
			'projectId' => $this->getOAuthCredentials()['project_id'],
			'signupUrlName' => $this->getSignupUrl()['name'],
			'enterpriseToken' => $enterpriseToken
		]), json_encode([
			'enterpriseDisplayName' => $this->getCompanyName(),
		]));
		if (empty($response['name']))
			throw new \RuntimeException('Unexpected server response: ' . json_encode($response));
		$this->db->insertOrUpdateSettingByKey('google-enterprise', json_encode($response));
	}
	function getEnterprise()
	{
		$data = $this->db->settings->get('google-enterprise');
		if (!$data) throw new \RuntimeException('No enterprise found');
		$data = json_decode($data, true);
		if (!$data) throw new \RuntimeException('Unable to parse JSON');
		if (empty($data['name']))
			throw new \RuntimeException('"name" missing!');
		return $data;
	}

	function patchPolicy(string $policyId, array $policyValues, string $deviceId = null)
	{
		$enterpriseName = $this->getEnterprise()['name'];
		$this->apiCall('PATCH', self::ANDROID_MANAGEMENT_API_URL . '/' . $enterpriseName . '/policies/' . urlencode($policyId), json_encode(array_merge(self::DEFAULT_POLICIES, $policyValues)));

		if ($deviceId) {
			$this->apiCall('PATCH', self::ANDROID_MANAGEMENT_API_URL . '/' . $enterpriseName . '/devices/' . urlencode($deviceId) . '?updateMask=policyName', json_encode([
				'policyName' => $policyId
			]));
		}
	}

	function generateEnrollmentToken()
	{
		$enterpriseName = $this->getEnterprise()['name'];

		// create dummy policy since a policy is mandatory
		$this->patchPolicy('default', [
			'name' => 'policies/default'
		]);

		$response = $this->apiCall('POST', self::ANDROID_MANAGEMENT_API_URL . '/' . $enterpriseName . '/enrollmentTokens', json_encode([
			'policyName' => $enterpriseName . '/policies/default'
		]));
		if (empty($response['qrCode']))
			throw new \RuntimeException('Unexpected server response: ' . json_encode($response));
		return $response['qrCode'];
	}

	function getDevices()
	{
		$enterpriseName = $this->getEnterprise()['name'];
		$response = $this->apiCall('GET', self::ANDROID_MANAGEMENT_API_URL . '/' . $enterpriseName . '/devices', null);
		if (empty($response['devices']))
			throw new \RuntimeException('Unexpected server response: ' . json_encode($response));
		return $response['devices'];
	}

	function generateWebToken()
	{
		$enterpriseName = $this->getEnterprise()['name'];
		$response = $this->apiCall('POST', self::ANDROID_MANAGEMENT_API_URL . '/' . $enterpriseName . '/webTokens', json_encode([
			'parentFrameUrl' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
		]));
		if (empty($response['value']))
			throw new \RuntimeException('Unexpected server response: ' . json_encode($response));
		return $response['value'];
	}

	function issueCommand(string $deviceId, string $command, array $params = [])
	{
		$enterpriseName = $this->getEnterprise()['name'];
		$response = $this->apiCall('POST', self::ANDROID_MANAGEMENT_API_URL . '/' . $enterpriseName . '/devices/' . urlencode($deviceId) . ':issueCommand', json_encode(
			array_merge(['type' => $command], $params)
		));
		if (empty($response['name']))
			throw new \RuntimeException('Unexpected server response: ' . json_encode($response));
		$nameSplitter = explode('/', $response['name']);
		return end($nameSplitter);
	}

	function getOperation(string $deviceId, string $operationId)
	{
		$enterpriseName = $this->getEnterprise()['name'];
		$response = $this->apiCall('GET', self::ANDROID_MANAGEMENT_API_URL . '/' . $enterpriseName . '/devices/' . urlencode($deviceId) . '/operations/' . urlencode($operationId), null);
		if (empty($response['name']))
			throw new \RuntimeException('Unexpected server response: ' . json_encode($response));
		return $response;
	}

	public function syncDevices()
	{
		foreach ($this->getDevices() as $device) {
			if (empty($device['hardwareInfo']['serialNumber'])) continue;
			$serial = $device['hardwareInfo']['serialNumber'];
			$udidSplitter = explode('/', $device['name']);
			$state = $device['state'];
			$model = ($device['hardwareInfo']['brand'] ?? '') . ' ' . ($device['hardwareInfo']['model'] ?? '');
			$family = $device['hardwareInfo']['hardware'] ?? '';
			$os = 'Android';
			if (!empty($device['softwareInfo']))
				$os .= ' ' . ($device['softwareInfo']['androidVersion'] ?? '') . ' ' . ($device['softwareInfo']['androidBuildNumber'] ?? '');

			$md = $this->db->selectMobileDeviceBySerialNumber($serial);
			if ($md) {
				$mdId = $md->id;
				$this->db->updateMobileDevice(
					$md->id,
					end($udidSplitter),
					$state,
					$md->device_name,
					$md->serial,
					''/*description*/,
					$model ? $model : $md->model,
					$os ? $os : $md->os,
					$family,
					''/*color*/,
					$device['policy_name'] ?? null,
					$md->push_token,
					$md->push_magic,
					$md->push_sent,
					$md->unlock_token,
					json_encode($device),
					$md->policy,
					$md->notes,
					$md->force_update,
					empty($device['lastStatusReportTime']) ? false : date('Y-m-d H:i:s', strtotime($device['lastStatusReportTime']))
				);
			} else {
				echo 'Creating device ' . $serial . '...' . "\n";
				$mdId = $this->db->insertMobileDevice(
					end($udidSplitter),
					$state,
					''/*name*/,
					$serial,
					''/*description*/,
					$model,
					$os,
					$family,
					''/*color*/,
					$device['policy_name'] ?? null,
					null/*push_token*/,
					null/*push_magic*/,
					null/*push_sent*/,
					null/*unlock_token*/,
					json_encode($device),
					''/*notes*/,
					0/*force_update*/
				);
			}

			$apps = [];
			foreach ($device['applicationReports'] ?? [] as $app) {
				if (empty($app['packageName']) || empty($app['displayName']) || empty($app['versionName']) || empty($app['versionCode'])) continue;
				$apps[] = [
					'identifier' => $app['packageName'],
					'name' => $app['displayName'],
					'display_version' => $app['versionName'],
					'version' => $app['versionCode'],
				];
			}
			$this->db->updateMobileDeviceApps($mdId, $apps);
		}
	}

	public function syncCommands()
	{
		foreach ($this->db->selectAllMobileDeviceCommand() as $mdc) {
			$md = $this->db->selectMobileDevice($mdc->mobile_device_id);
			if ($md->getOsType() != \Models\MobileDevice::OS_TYPE_ANDROID) continue;
			if (
				$mdc->state == \Models\MobileDeviceCommand::STATE_SUCCESS
				|| $mdc->state == \Models\MobileDeviceCommand::STATE_FAILED
			) continue;

			$op = $this->getOperation($md->udid, $mdc->external_id);
			if (!empty($op['done'])) {
				$state = null;
				if (!empty($op['error']) || !empty($op['metadata']['errorCode'])) {
					$state = \Models\MobileDeviceCommand::STATE_FAILED;
				} else {
					$state = \Models\MobileDeviceCommand::STATE_SUCCESS;
				}
				$this->db->updateMobileDeviceCommand($mdc->id, $mdc->mobile_device_id, $mdc->name, $mdc->parameter, $state, json_encode($op), date('Y-m-d H:i:s'));
			}
		}
	}
}
