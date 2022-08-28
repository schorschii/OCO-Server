<?php

class LanguageController {

	// directory with translation files
	const LANG_DIR = __DIR__.'/Language';

	// the base language which *must* be fully translated
	const DEFAULT_LANG = 'en';

	public static function getMessage($key) {
		$messages = self::getMessages();
		if(isset($messages[$key])) return $messages[$key];
		else return $key; // fallback
	}

	private static function getMessages() {
		// load default language
		$messages = require(self::LANG_DIR.'/'.self::DEFAULT_LANG.'.php');

		// override translated messages with user's desired language
		if(!empty($_POST['lang'])) { // evaluate POST parameter (API calls)
			$messages = array_merge( $messages, self::loadFile($_POST['lang']) );
		}
		elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { // evaluate HTTP header (webfrontend)
			$messages = array_merge( $messages, self::loadFile(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)) );
		}

		return $messages;
	}

	private static function loadFile($langcode) {
		$fileName = self::LANG_DIR.'/' . preg_replace('/[^a-zA-Z0-9_-]/s', '', $langcode) . '.php';
		if(file_exists($fileName)) {
			return require($fileName);
		}
		return [];
	}

}
