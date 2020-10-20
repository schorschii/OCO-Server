<?php

session_start();

if(!isset($_SESSION['um_username'])) {
	redirectToLogin();
}

function redirectToLogin() {
	global $SUBVIEW;
	header('HTTP/1.1 401 Not Authorized');
	if(empty($SUBVIEW)) {
		header('Location: login.php');
	}
	die();
}
