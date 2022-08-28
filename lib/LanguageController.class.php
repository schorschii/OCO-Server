<?php

class LanguageController {

	// directory with translation files
	const LANG_DIR = __DIR__.'/Language';

	// the base language which *must* be fully translated
	const DEFAULT_LANG = 'en';

	// compiled messages (base language + translated strings)
	private $messages;

	// we do not want to initialize the LanguageController on every getMessage() call, so we save the object in a singleton
	private static $singleton;

	function __construct($langCodes=[]) {
		// load default language
		$this->messages = require(self::LANG_DIR.'/'.self::DEFAULT_LANG.'.php');

		// override default messages with translated messages of user's desired language
		if(!empty($_POST['lang'])) { // evaluate POST parameter (API calls)
			$langCodes[] = $_POST['lang'];
		}
		elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { // evaluate HTTP header (webfrontend)
			$langCodes[] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}
		foreach($langCodes as $langCode) {
			$this->messages = array_merge( $this->messages, $this->loadFile($langCode) );
		}
	}

	public function getMessage($key) {
		if(isset($this->messages[$key])) return $this->messages[$key];
		else return $key; // fallback
	}

	private function loadFile($langCode) {
		if(empty($langCode)) return [];
		$fileName = self::LANG_DIR.'/' . preg_replace('/[^a-zA-Z0-9_-]/s', '', $langCode) . '.php';
		if(file_exists($fileName)) {
			return require($fileName);
		}
		return [];
	}

	public static function getMessageFromSingleton($key) {
		if(!isset(self::$singleton)) {
			self::$singleton = new LanguageController();
		}
		return self::$singleton->getMessage($key);
	}

}
