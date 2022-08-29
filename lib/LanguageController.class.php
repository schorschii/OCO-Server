<?php

class LanguageController {

	// directory with translation files
	const DEFAULT_LANG_DIR = __DIR__.'/Language';

	// the base language which *must* be fully translated
	const DEFAULT_LANG = 'en';

	// extra language directories
	protected $langDirs = [self::DEFAULT_LANG_DIR];

	// compiled messages (base language + translated strings)
	private $messages = [];

	// we do not want to initialize the LanguageController (= merge the arrays) on every getMessage() call,
	// so we save the object in a singleton
	private static $singleton;

	function __construct($langCodes=[]) {
		$this->loadTranslations($langCodes);
	}

	private function loadTranslations($langCodes=[]) {
		$this->messages = [];

		// load default language
		foreach($this->langDirs as $langDir) {
			$this->messages = array_merge( $this->messages, require($langDir.'/'.self::DEFAULT_LANG.'.php') );
		}

		// override default messages with translated messages of user's desired language
		if(!empty($_POST['lang'])) { // evaluate POST parameter (API calls)
			$langCodes[] = $_POST['lang'];
		}
		elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { // evaluate HTTP header (webfrontend)
			$langCodes[] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}
		foreach($langCodes as $langCode) {
			$this->messages = array_merge( $this->messages, $this->loadTranslationFile($langCode) );
		}
	}

	public function getMessage($key) {
		if(isset($this->messages[$key])) return $this->messages[$key];
		else return $key; // fallback
	}

	private function loadTranslationFile($langCode) {
		if(empty($langCode)) return [];
		$messages = [];
		foreach($this->langDirs as $langDir) {
			$fileName = $langDir.'/' . preg_replace('/[^a-zA-Z0-9_-]/s', '', $langCode) . '.php';
			if(file_exists($fileName)) {
				$messages = array_merge( $messages, require($fileName) );
			}
		}
		return $messages;
	}

	private static function initializeSingleton() {
		if(!isset(self::$singleton)) {
			self::$singleton = new LanguageController();
		}
	}

	public static function getMessageFromSingleton($key) {
		self::initializeSingleton();
		return self::$singleton->getMessage($key);
	}

	public static function registerTranslationDirInSingleton($dir) {
		self::initializeSingleton();
		self::$singleton->langDirs[] = $dir;
		self::$singleton->loadTranslations();
	}

}
