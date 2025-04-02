<?php

require_once('conf.php');
spl_autoload_register(function ($class) {
	$file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
	$filePath = __DIR__.'/php/'.$file;
	if(file_exists($filePath)) {
		require_once($filePath);
		return true;
	}
	return false;
});
