<?php
require_once('../lib/loader.php');
session_start();

function authErrorExit() {
	header('HTTP/1.1 401 Client Not Authorized'); die();
}

// check if client is allowed to download
if(!isset($_SESSION['um_username'])) {
	if(empty($_GET['agent-key']) || empty($_GET['hostname'])) {
		authErrorExit();
	}
	$computer = $db->getComputerByName($_GET['hostname']);
	if($computer == null || $_GET['agent-key'] != $computer->agent_key) {
		authErrorExit();
	}
}

// get package and start download
if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
	$path = PACKAGE_PATH.'/'.intval($package->id).'.zip';
	if(file_exists($path)) {
		try {
			$download = new ResumeDownload($path);
			$download->process();
		} catch (Exception $e) {
			header('HTTP/1.1 404 File Not Found');
			die('Sorry, an error occured.');
		}
	}
}
