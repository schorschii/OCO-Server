<?php

// prepare PHP session options
$secure = false;
if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
	$secure = true;
	header('strict-transport-security: max-age=15552000; includeSubDomains');
}
if(version_compare(PHP_VERSION, '7.3.0') >= 0) {
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => null,
		'domain' => null,
		'secure' => $secure,
		'httponly' => true,
		'samesite' => 'Lax'
	]);
} else {
	session_set_cookie_params(
		0 /* session_lifetime: 0 -> until browser closed */,
		null /* path: default */,
		null /* domain: default */,
		$secure,
		true /*http_only*/
	);
}
session_start();
