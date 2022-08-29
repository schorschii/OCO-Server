<?php

class ExtensionController {

	const EXTENSION_DIR = __DIR__.'/../extensions';

	private $extensions = [];

	function __construct() {
		// load all extensions
		foreach(glob(self::EXTENSION_DIR.'/*/index.php') as $fileName) {
			$extConf = require_once($fileName);
			if(empty($extConf['id'])) continue;
			if(isset($extConf['oco-version-min']) && version_compare($extConf['oco-version-min'], OcoServer::APP_VERSION, '>')) continue;
			if(isset($extConf['oco-version-max']) && version_compare($extConf['oco-version-max'], OcoServer::APP_VERSION, '<')) continue;
			$this->extensions[$extConf['id']] = $extConf;
		}

		// load all extension translations
		foreach($this->getAggregatedConf('translation-dir') as $langDir) {
			LanguageController::registerTranslationDirInSingleton($langDir);
		}

		// register all autoloader
		foreach($this->getAggregatedConf('autoload') as $dir) {
			if(!is_dir($dir)) continue;
			spl_autoload_register(function ($class) use($dir) {
				$file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.class.php';
				$filePath = $dir.'/'.$file;
				if(file_exists($filePath)) {
					require_once($filePath);
					return true;
				}
				return false;
			});
		}
	}

	public function getAggregatedConf($key) {
		$aggregatedConf = [];
		foreach($this->extensions as $extConf) {
			if(isset($extConf[$key])) {
				if(is_array($extConf[$key])) {
					$aggregatedConf = array_merge( $aggregatedConf, $extConf[$key] );
				} else {
					$aggregatedConf[] = $extConf[$key];
				}
			}
		}
		return $aggregatedConf;
	}

}
