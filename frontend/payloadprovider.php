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
session_write_close();

// get package and start download
if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
	if($package === null) {
		header('HTTP/1.1 404 Not Found'); die();
	}

	$path = PACKAGE_PATH.'/'.intval($package->id).'.zip';
	if(!file_exists($path)) {

		header('HTTP/1.1 404 Not Found'); die();

	} else {

		if(empty($_GET['resumable'])) {
			// use direct download via readfile() by default because its much faster
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: Binary');
			header('Content-disposition: attachment; filename="'.basename($path).'"');
			ob_clean(); flush();
			readfile($path);
		} else {
			// use resumable download if requested
			try {
				$download = new ResumeDownload($path);
				$download->process();
			} catch(Exception $e) {
				header('HTTP/1.1 404 File Not Found');
				die('Sorry, an error occured.');
			}
		}

	}
}
