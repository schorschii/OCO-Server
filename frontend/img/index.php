<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');

// this script is called via Apache RewriteRule if the requested filename was not found in this directory
// in this case, we check if there is an extension available which can handle this request

$requestUrl = explode('?', $_SERVER['REQUEST_URI']);
$requestUrlPath = reset($requestUrl);
$requestUrl = explode('/', $requestUrlPath);
$requestUrlFile = end($requestUrl);

$extViews = $ext->getAggregatedConf('frontend-img');
if(isset($extViews[$requestUrlFile]) && file_exists($extViews[$requestUrlFile])) {
	$mimeType = mime_content_type($extViews[$requestUrlFile]);
	if(empty($mimeType) || $mimeType == 'image/svg') $mimeType = 'image/svg+xml';
	header('Content-Type: '.$mimeType);
	header('Content-Length: '.filesize($extViews[$requestUrlFile]));
	header('Etag: '.md5_file($extViews[$requestUrlFile]));
	header('Cache-Control: max-age='.(60*60*24*30));
	readfile($extViews[$requestUrlFile]);
} else {
	header('HTTP/1.1 404 Not Found'); die();
}
