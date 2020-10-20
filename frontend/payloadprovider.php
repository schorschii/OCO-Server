<?php
require_once('../lib/loader.php');
session_write_close();

if(!empty($_GET['client-key']) && $_GET['client-key'] !== $db->getSettingByName('client-key')) {
	header('HTTP/1.1 401 Client Not Authorized'); die();
}

if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
	$path = PACKAGE_PATH.'/'.$package->filename;
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
