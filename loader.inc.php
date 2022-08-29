<?php

// static imports
require_once(__DIR__.'/conf.php');
require_once(__DIR__.'/lib/Tools.php');

// dynamic class imports
spl_autoload_register(function ($class) {
	$file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.class.php';
	$filePath = __DIR__.'/lib/'.$file;
	if(file_exists($filePath)) {
		require_once($filePath);
		return true;
	}
	return false;
});

// global database handle object
$db = new DatabaseController();

// load extensions
$ext = new ExtensionController();

// housekeeping without cron
if(DO_HOUSEKEEPING_BY_WEB_REQUESTS) {
	$houseKeeping = new HouseKeeping($db);
	$houseKeeping->houseKeeping();
}
