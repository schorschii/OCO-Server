<?php

session_start();

// check if user has a valid session
if(!isset($_SESSION['um_username'])) {
	redirectToLogin();
}
// check if user account still exists and is not locked
$systemUser = $db->getSystemUser($_SESSION['um_userid']);
if($systemUser === null || !empty($systemUser->locked)) {
	redirectToLogin(true);
}

function redirectToLogin($forceLogout=false) {
	global $SUBVIEW;
	header('HTTP/1.1 401 Not Authorized');
	if(empty($SUBVIEW)) {
		header('Location: login.php'.($forceLogout ? '?logout=1' : ''));
	}
	die();
}
