<?php
if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

require_once(__DIR__.'/../loader.inc.php');

$policyDefintions = $db->selectAllPolicyDefinition();

foreach($policyDefintions as $pd) {
	if(!$pd->manifestation_windows) continue;
	$mWindowsSplit = explode(':', $pd->manifestation_windows);
	if(count($mWindowsSplit) != 3) continue;

	if($mWindowsSplit[1] == 'Software\Policies\Google\Chrome') {
		$mLinux = 'JSON:/etc/opt/chrome/policies/managed/default.json:'.$mWindowsSplit[2]."\n"
			.'JSON:/etc/chromium/policies/managed/default.json:'.$mWindowsSplit[2];
	} elseif($mWindowsSplit[1] == 'Software\Policies\Google\Chrome\Recommended') {
		$mLinux = 'JSON:/etc/opt/chrome/policies/recommended/default.json:'.$mWindowsSplit[2]."\n"
			.'JSON:/etc/chromium/policies/recommended/default.json:'.$mWindowsSplit[2];
	} elseif(startsWith($mWindowsSplit[1], 'Software\Policies\Mozilla\Firefox')) {
		$prefix = substr($mWindowsSplit[1], 33, 1) == '\\' ? substr($mWindowsSplit[1], 34) : ''; // cut first backslash!
		if(!empty($prefix)) $prefix .= '\\';
		$prefix = 'policies\\'.$prefix;
		$mLinux = 'JSON:/etc/firefox/policies/policies.json:'.$prefix.$mWindowsSplit[2];
	} else {
		continue;
	}

	echo 'Set Linux manifestation: '.$mWindowsSplit[2]."\n";
	$stmt = $db->getDbHandle()->prepare(
		'UPDATE policy_definition SET manifestation_linux = :manifestation_linux WHERE id = :id'
	);
	$stmt->execute([':id' => $pd->id, ':manifestation_linux' => $mLinux]);
}
