<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

// this script is called via Apache RewriteRule if the requested filename was not found in this directory
// in this case, we check if there is an extension available which can handle this request

$requestUrl = explode('?', $_SERVER['REQUEST_URI']);
$requestUrlPath = reset($requestUrl);
$requestUrl = explode('/', $requestUrlPath);
$requestUrlFile = end($requestUrl);

$extViews = $ext->getAggregatedConf('self-service-ajax-handler');
if(isset($extViews[$requestUrlFile]) && file_exists($extViews[$requestUrlFile])) {
    require($extViews[$requestUrlFile]);
} else {
	header('HTTP/1.1 404 Not Found'); die();
}
