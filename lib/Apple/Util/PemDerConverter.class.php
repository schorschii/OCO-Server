<?php

namespace Apple\Util;

class PemDerConverter {

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

}
