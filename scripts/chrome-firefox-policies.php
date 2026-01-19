<?php
if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

require_once(__DIR__.'/../loader.inc.php');

$policyDefintions = $db->selectAllPolicyDefinition();

foreach($policyDefintions as $pd) {
	if(!$pd->manifestation_windows) continue;
	$mWindowsSplit = explode(':', $pd->manifestation_windows);
	if(count($mWindowsSplit) < 2) continue;

	$splitter = explode('\\', $mWindowsSplit[1]);
	$valueName = $mWindowsSplit[2] ?? end($splitter);

	if(startsWith($mWindowsSplit[1], 'Software\Policies\Google\Chrome')) {
		$mLinux = 'JSON:/etc/opt/chrome/policies/managed/default.json:'.$valueName."\n"
			.'JSON:/etc/chromium/policies/managed/default.json:'.$valueName;
		$mMacOs = 'DEFAULTS:/Library/Preferences/com.google.Chrome:'.$valueName;

	} elseif(startsWith($mWindowsSplit[1], 'Software\Policies\Google\Chrome\Recommended')) {
		$mLinux = 'JSON:/etc/opt/chrome/policies/recommended/default.json:'.$valueName."\n"
			.'JSON:/etc/chromium/policies/recommended/default.json:'.$valueName;
		$mMacOs = null;

	} elseif(startsWith($mWindowsSplit[1], 'Software\Policies\Mozilla\Firefox')) {
		$prefix = substr($mWindowsSplit[1], 33, 1) == '\\' ? substr($mWindowsSplit[1], 34) : ''; // cut first backslash!
		if(!empty($prefix)) $prefix .= '\\';
		$prefix = 'policies\\'.$prefix;
		$mLinux = trim('JSON:/etc/firefox/policies/policies.json:'.$prefix.($mWindowsSplit[2] ?? ''), '\\');
		$mMacOs = null;

	} else {
		// neither a Chrome or Firefox policy
		continue;
	}

	echo 'Set Linux/macOS manifestation: #'.$pd->id.' '.$pd->name."\n";
	$stmt = $db->getDbHandle()->prepare(
		'UPDATE policy_definition SET manifestation_linux=:manifestation_linux, manifestation_macos=:manifestation_macos WHERE id = :id'
	);
	$stmt->execute([':id' => $pd->id, ':manifestation_linux' => $mLinux, ':manifestation_macos' => $mMacOs]);
}
