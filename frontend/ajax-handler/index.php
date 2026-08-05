<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

// this script is called via Apache RewriteRule for all requests of this directory

$requestUrl = explode('?', $_SERVER['REQUEST_URI']);
$requestUrlPath = reset($requestUrl);
$requestUrl = explode('/', $requestUrlPath);
$requestUrlFile = end($requestUrl);

$extViews = $ext->getAggregatedConf('frontend-ajax-handler');
// 1. check if an active extension overrides the file
if(isset($extViews[$requestUrlFile]) && file_exists($extViews[$requestUrlFile])) {
	require($extViews[$requestUrlFile]);
}
// 2. fallback to the original core file if it exists
else if(file_exists($requestUrlFile) && $requestUrlFile !== 'index.php') {
	require($requestUrlFile);
}
// 3. not found
else {
	header('HTTP/1.1 404 Not Found'); die();
}
