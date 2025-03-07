<?php

namespace Apple\Util;

class JsonWebToken {

	static function base64UrlEncode($data) {
		$encoded = base64_encode($data);
		$encoded = strtr($encoded, '+/', '-_');
		$encoded = rtrim($encoded, '=');
		return $encoded;
	}

	static function generateJwt($alg, $key, $kid, $tid, $aud=null, $scope=null) {
		$jwtHead = json_encode(['alg'=>$alg, 'kid'=>$kid]);

		$jwtBodyData = ['iss'=>$tid, 'iat'=>time(), 'exp'=>strtotime('+1 hour')];
		if($aud) $jwtBodyData['aud'] = $aud;
		if($scope) $jwtBodyData['scope'] = $scope;
		$jwtBody = json_encode($jwtBodyData);

		$jwt = self::base64UrlEncode($jwtHead).'.'.self::base64UrlEncode($jwtBody);

		$key = openssl_pkey_get_private($key, null);
		openssl_sign($jwt, $signature, $key, OPENSSL_ALGO_SHA256);
		if(substr(strtoupper($alg), 0, 2) == 'ES')
			$signature = ECSignature::fromDER($signature, 64);

		return $jwt.'.'.self::base64UrlEncode($signature);
	}

}
