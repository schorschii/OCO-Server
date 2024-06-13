<?php

require_once('session-options.inc.php');

// check if user has a valid session and is authenticated (logged in)
$selfServiceEnabled = boolval($db->settings->get('self-service-enabled'));
if(!isset($_SESSION['oco_self_service_user_id']) || !$selfServiceEnabled) {
	redirectToLogin();
}
// check if user account still exists and is not locked
if(!isset($db)) { header('Location: index.php'); die(); }
$currentDomainUser = $db->selectDomainUser($_SESSION['oco_self_service_user_id']);
if(empty($currentDomainUser) || empty($currentDomainUser->domain_user_role_id)) {
	redirectToLogin(true);
}
// initialize global CoreLogic with user context
$cl = new SelfService\CoreLogic($db, $currentDomainUser);

function redirectToLogin($forceLogout=false) {
	global $SUBVIEW;
	header('HTTP/1.1 401 Not Authorized');
	if(empty($SUBVIEW)) {
		$params = [];
		if($forceLogout) {
			$params[] = 'logout=1';
		}
		if(!empty($_SERVER['REQUEST_URI']) && startsWith($_SERVER['REQUEST_URI'], '/')) {
			$params[] = 'redirect='.urlencode($_SERVER['REQUEST_URI']);
		}
		header('Location: login.php'.(empty($params) ? '' : '?').implode('&', $params));
	}
	die();
}
