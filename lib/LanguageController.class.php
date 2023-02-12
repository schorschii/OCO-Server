<?php

class LanguageController {

	// directory with translation files
	const DEFAULT_LANG_DIR = __DIR__.'/Language';

	// the base language which *must* be fully translated
	const DEFAULT_LANG = 'en';

	// extra language directories
	protected $langDirs = [self::DEFAULT_LANG_DIR];

	// language codes in the preferred order to load
	protected $langCodes = [];

	// compiled messages (base language + translated strings)
	private $messages = [];

	// we do not want to initialize the LanguageController (= merge the arrays) on every getMessage() call,
	// so we save the object in a singleton
	private static $singleton;

	function __construct($langCodes=[]) {
		// read language preferences
		$this->langCodes = $langCodes;
		if(!empty($_POST['lang'])) { // evaluate POST parameter (API calls)
			$this->langCodes[] = $_POST['lang'];
		}
		if(!empty($_SESSION['lang'])) { // evaluate SESSION parameter
			$this->langCodes[] = $_SESSION['lang'];
		}
		elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { // evaluate HTTP header (webfrontend)
			$this->langCodes[] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}

		$this->loadTranslations();
	}

	private function loadTranslations() {
		$this->messages = [];

		// load default language
		foreach($this->langDirs as $langDir) {
			$this->messages = array_merge( $this->messages, require($langDir.'/'.self::DEFAULT_LANG.'.php') );
		}

		// override default messages with translated messages of user's desired language
		foreach($this->langCodes as $langCode) {
			$this->messages = array_merge( $this->messages, $this->loadTranslationFile($langCode) );
		}
	}

	public function getMessage($key) {
		if(isset($this->messages[$key])) return $this->messages[$key];
		else return $key; // fallback
	}
	public function getMessages() {
		return $this->messages;
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

	public static function getSingleton() {
		if(!isset(self::$singleton)) {
			self::$singleton = new LanguageController();
		}
		return self::$singleton;
	}

	public static function getMessageFromSingleton($key) {
		self::getSingleton();
		return self::$singleton->getMessage($key);
	}

	public static function registerTranslationDirInSingleton($dir) {
		self::getSingleton();
		self::$singleton->langDirs[] = $dir;
		self::$singleton->loadTranslations();
	}

	public static function getTranslations() {
		$langCodes = [];
		foreach(glob(self::DEFAULT_LANG_DIR.'/*.{php}', GLOB_BRACE) as $file) {
			$langCodes[] = explode('.', basename($file))[0];
		}
		return $langCodes;
	}

	public function getCurrentLangCode() {
		if(empty($this->langCodes)) return null;
		else return $this->langCodes[0];
	}

}
