<?php
const LANG_DIR = __DIR__.'/lang';
const DEFAULT_LANG = 'en';

function langfile($langcode) {
	$filename = LANG_DIR.'/' . preg_replace('/[^a-zA-Z0-9_-]/s', '', $langcode) . '.php';
	if(file_exists($filename)) {
		return $filename;
	}
	return null;
}

$langfile = null;

// language as POST parameter (API calls)
if($langfile === null && !empty($_POST['lang'])) {
	$langfile = langfile($_POST['lang']);
}
// language as HTTP header (webfrontend)
if($langfile === null && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$langfile = langfile(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
}
// fallback language
if($langfile === null) {
	$langfile = LANG_DIR.'/'.DEFAULT_LANG.'.php';
}

require_once($langfile);
