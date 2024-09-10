<?php

namespace Apple;

class PushNotificationService {

	const SANDBOX_SERVER   = 'https://api.development.push.apple.com';
	const LIVE_SERVER      = 'https://api.push.apple.com';

	const DEFAULT_PORT     = 443;
	const ALTERNATIVE_PORT = 2197;

	const CLIENT_CERT_PATH = '/tmp/apnscert.pem';

	private $db;
	private $ch;

	function __construct(\DatabaseController $db, string $topic, $cert, $certKey) {
		$this->db = $db;
		$this->ch = curl_init();
		$this->topic = $topic;

		if($cert instanceof \OpenSSLCertificate) {
			openssl_x509_export($cert, $certPem);
			$cert = $certPem;
		}
		if($certKey instanceof \OpenSSLAsymmetricKey) {
			openssl_pkey_export($certKey, $pkeyPem, null);
			$certKey = $pkeyPem;
		}
		$certAndKeyPem = $certKey.$cert;
		file_put_contents(self::CLIENT_CERT_PATH, $certAndKeyPem);
	}

	function __destruct() {
		unlink(self::CLIENT_CERT_PATH);
		curl_close($this->ch);
	}

	function send(string $push_token, string $push_magic, $priority=10) {
		$push_token_hex = bin2hex(base64_decode($push_token));
		curl_setopt($this->ch, CURLOPT_URL, self::LIVE_SERVER.'/3/device/'.urlencode($push_token_hex));
		curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
		curl_setopt($this->ch, CURLOPT_SSLCERT, self::CLIENT_CERT_PATH);
		#curl_setopt($this->ch, CURLOPT_VERBOSE, true);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
			'apns-priority: '.$priority,
			'apns-push-type: mdm',
			'apns-topic: '.$this->topic,
			'content-type: application/json',
		]);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode(['mdm' => $push_magic]));
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($this->ch);
		#var_dump($response);
		$statusCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if($statusCode !== 200) {
			throw new \RuntimeException('Got unexpected APNS status code '.$statusCode);
		}
	}

}
