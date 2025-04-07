#!/usr/bin/env php
<?php
/* To activate this local check in the CheckMK agent, execute:
   cd /usr/lib/check_mk_agent/local/
   sudo ln -s /srv/www/oco/scripts/checkmk.php
*/
if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

require_once(__DIR__.'/../loader.inc.php');


try {

	// check license
	$license = new LicenseCheck($db);
	if(!$license->isValid()) {
		echo '3 "OCO License" - License is invalid!' . "\n";
	} elseif(!$license->isFree() && $license->getRemainingTime() < 60*60*24*14) {
		$remainingDays = round($license->getRemainingTime() / (60*60*24));
		echo '2 "OCO License" - License expires in '.$remainingDays.' day(s)' . "\n";
	} else {
		echo '0 "OCO License" - License is valid until '.date('Y-m-d', $license->getExpireTime()) . "\n";
	}

	// TODO: check last cron run

} catch(Exception $e) {
	echo $argv[1].' ERROR: '.$e->getMessage()."\n";
	echo $e->getTraceAsString();
	echo "\n";
	exit(1);
}
