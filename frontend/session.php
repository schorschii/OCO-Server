<?php

session_start();

// check if user has a valid session
if(!isset($_SESSION['oco_username'])) {
	redirectToLogin();
}
// check if user account still exists and is not locked
if(!isset($db)) { header('Location: index.php'); die(); }
$currentSystemUser = $db->selectSystemUser($_SESSION['oco_user_id']);
if(empty($currentSystemUser) || !empty($currentSystemUser->locked)) {
	redirectToLogin(true);
}
// initialize global CoreLogic with user context
$cl = new CoreLogic($db, $currentSystemUser);

function redirectToLogin($forceLogout=false) {
	global $SUBVIEW;
	header('HTTP/1.1 401 Not Authorized');
	if(empty($SUBVIEW)) {
		if(!empty($_SERVER['REQUEST_URI']) && startsWith($_SERVER['REQUEST_URI'], '/'))
			$_SESSION['oco_login_redirect'] = $_SERVER['REQUEST_URI'];
		header('Location: login.php'.($forceLogout ? '?logout=1' : ''));
	}
	die();
}
