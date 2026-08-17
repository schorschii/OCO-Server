<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');

// this script is called via Apache RewriteRule for all requests of this directory

$requestUrl = explode('?', $_SERVER['REQUEST_URI']);
$requestUrlPath = reset($requestUrl);
$requestUrl = explode('/', $requestUrlPath);
$requestUrlFile = end($requestUrl);

$extViews = $ext->getAggregatedConf('frontend-img');
// 1. check if an active extension overrides the file
if(isset($extViews[$requestUrlFile]) && file_exists($extViews[$requestUrlFile])) {
	serveImg($extViews[$requestUrlFile]);
}
// 2. fallback to the original core file if it exists
else if(file_exists($requestUrlFile) && $requestUrlFile !== 'index.php') {
	serveImg($requestUrlFile);
}
// 3. not found
else {
	header('HTTP/1.1 404 Not Found'); die();
}

function serveImg($file) {
	$mimeType = mime_content_type($file);
	if(empty($mimeType) || $mimeType == 'image/svg') $mimeType = 'image/svg+xml';
	header('Content-Type: '.$mimeType);
	header('Content-Length: '.filesize($file));
	header('Etag: '.md5_file($file));
	header('Cache-Control: max-age='.(60*60*24*30));
	readfile($file);
}
