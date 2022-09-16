<?php
require_once('../loader.inc.php');
require_once('session.php');

// do not block other frontend requests
session_write_close();

// get package and start download
$package = $db->selectPackage($_GET['id'] ?? -1);
if($package === null || !$package->getFilePath()) {
	header('HTTP/1.1 404 Not Found'); die();
}

// check if system user is allowed to download
if(!$currentSystemUser->checkPermission($package, PermissionManager::METHOD_DOWNLOAD, false)) {
	header('HTTP/1.1 403 Forbidden'); die();
}

try {
	$package->download();
} catch(Exception $e) {
	header('HTTP/1.1 500 Internal Server Error'); die();
}
