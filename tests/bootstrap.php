<?php

// Autoload OCO classes
spl_autoload_register(function (string $class): bool {
	$file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.class.php';
	$path = __DIR__ . '/../lib/' . $file;
	if(file_exists($path)) {
		require_once $path;
		return true;
	}
	return false;
});

// LanguageController must be loaded before Tools.php (which defines LANG())
require_once __DIR__ . '/../lib/LanguageController.class.php';
require_once __DIR__ . '/../lib/Tools.php';
require_once __DIR__ . '/../lib/Html.class.php';
