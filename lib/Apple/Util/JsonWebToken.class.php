<?php

namespace Apple\Util;

class JsonWebToken {

	static function base64UrlEncode($data) {
		$encoded = base64_encode($data);
		$encoded = strtr($encoded, '+/', '-_');
		$encoded = rtrim($encoded, '=');
		return $encoded;
	}

	static function generateJwtEs256($key, $kid, $tid) {
		$jwtHead = json_encode(['alg'=>'ES256','kid'=>$kid]);
		$jwtBody = json_encode(['iss'=>$tid,'iat'=>time(),'exp'=>strtotime('+1 hour')]);
		$jwt = self::base64UrlEncode($jwtHead).'.'.self::base64UrlEncode($jwtBody);
		$key = openssl_pkey_get_private($key, null);
		openssl_sign($jwt, $signature, $key, OPENSSL_ALGO_SHA256);
		$signature = ECSignature::fromDER($signature, 64);
		return $jwt.'.'.self::base64UrlEncode($signature);
	}

}
