<?php

require_once('session-options.inc.php');

// check if user has a valid session and is authenticated (logged in)
if(!isset($_SESSION['oco_user_id'])) {
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
		$params = [];
		if($forceLogout) {
			$params['logout'] = '1';
		}
		if(!empty($_SERVER['REQUEST_URI']) && startsWith($_SERVER['REQUEST_URI'], '/')) {
			$params['redirect'] = $_SERVER['REQUEST_URI'];
		}
		header('Location: login.php'.(empty($params) ? '' : '?').http_build_query($params));
	}
	die();
}
