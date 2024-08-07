<?php

class MsiInfo {

	const WINEPREFIX = '/tmp/.wine-oco';

	private $properties = [];

	function __construct(string $msiFile) {
		if(!file_exists(self::WINEPREFIX)) {
			mkdir(self::WINEPREFIX);
		}
		$result = exec('WINEPREFIX='.self::WINEPREFIX.' wine msidb.exe -d '.escapeshellarg($msiFile).' -e -f '.escapeshellarg(self::WINEPREFIX).' Property 2>/dev/null', $output, $returnCode);
		if($result === false || $returnCode === 127) {
			throw new RuntimeException('Unable to execute msidb - is wine installed?');
		}
		if($returnCode !== 0) {
			throw new RuntimeException('msidb returned error '.$returnCode.' - maybe the MSI file is damaged?');
		}

		$tableFile = self::WINEPREFIX.'/Property.idt';
		if(is_file($tableFile)) {
			$tableContent = file_get_contents($tableFile);
			foreach(preg_split("/((\r?\n)|(\r\n?))/", $tableContent) as $line) {
				$splitter = preg_split("/\t/", $line);
				if(count($splitter) != 2) continue;
				$this->properties[$splitter[0]] = $splitter[1];
			}
		} else {
			throw new RuntimeException('Unable to read Property.idt - maybe the MSI file is damaged?');
		}
	}

	public function getProperty(string $prop) {
		return $this->properties[$prop] ?? null;
	}

	const RESOLVABLE_MSI_PROPERTIES = [
		'ProductCode', 'UpgradeCode',
		'ProductName', 'ProductVersion', 'Manufacturer'
	];
	static function resolvePlaceholders(array $files, array $strings) {
		$found = false;
		foreach(self::RESOLVABLE_MSI_PROPERTIES as $prop) {
			foreach($strings as $key => $origValue) {
				if(strpos($origValue, '$$'.$prop.'$$') !== false) {
					$found = true;
					break 2;
				}
			}
		}
		// inspect MSI files only if at least one MSI placeholder was found
		if($found) {
			foreach($files as $fileName => $filePath) {
				$mimeType = mime_content_type($filePath);
				if($mimeType === 'application/x-msi') {
					$msi = new MsiInfo($filePath);
					foreach($strings as $key => $origValue) {
						foreach(self::RESOLVABLE_MSI_PROPERTIES as $prop) {
							$propValue = $msi->getProperty($prop);
							if($propValue) {
								$strings[$key] = str_replace('$$'.$prop.'$$', $propValue, $strings[$key]);
							}
						}
					}
					break;
				}
			}
		}
		return $strings;
	}

}
