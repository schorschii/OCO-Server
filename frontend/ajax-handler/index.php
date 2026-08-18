<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

// this script is called via Apache RewriteRule for all requests of this directory

$requestUrl = explode('?', $_SERVER['REQUEST_URI']);
$requestUrlPath = reset($requestUrl);
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if(substr($requestUrlPath, 0, strlen($basePath)) === $basePath)
	$requestUrlPath = ltrim(substr($requestUrlPath, strlen($basePath)), '/');

$extViews = $ext->getAggregatedConf('frontend-ajax-handler');
// 1. check if an active extension overrides the file
if(isset($extViews[$requestUrlPath]) && file_exists($extViews[$requestUrlPath])) {
	require($extViews[$requestUrlPath]);
}
// 2. fallback to the original core file if it exists
elseif(file_exists(__DIR__.'/'.$requestUrlPath) && $requestUrlPath !== 'index.php') {
	require(__DIR__.'/'.$requestUrlPath);
}
// 3. not found
else {
	header('HTTP/1.1 404 Not Found'); die();
}
