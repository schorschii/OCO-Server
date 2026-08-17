<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');

// this script is called via Apache RewriteRule for all requests of this directory

$requestUrl = explode('?', $_SERVER['REQUEST_URI']);
$requestUrlPath = reset($requestUrl);
$requestUrl = explode('/', $requestUrlPath);
$requestUrlFile = end($requestUrl);

$extViews = $ext->getAggregatedConf('frontend-js');
// 1. check if an active extension overrides the file
if(isset($extViews[$requestUrlFile]) && file_exists($extViews[$requestUrlFile])) {
	header('Content-Type: text/javascript');
	readfile($extViews[$requestUrlFile]);
}
// 2. fallback to the original core file if it exists
else if(file_exists($requestUrlFile) && $requestUrlFile !== 'index.php') {
	if(substr($requestUrlFile, -4) == '.php') {
		require($requestUrlFile);
	} else {
		header('Content-Type: text/javascript');
		readfile($requestUrlFile);
	}
}
// 3. not found
else {
	header('HTTP/1.1 404 Not Found'); die();
}
