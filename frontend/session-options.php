<?php

$secure = false;
if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $secure = true;
    header('strict-transport-security: max-age=15552000; includeSubDomains');
}

session_set_cookie_params(0/*session_lifetime*/, null, null, $secure, true/*http_only*/);
session_start();
