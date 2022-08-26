<?php
if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

if(!isset($argv[1]))
	die('Please specify an action as first parameter (housekeeping|ldapsync).'."\n");

require_once(__DIR__.'/lib/Loader.php');

switch($argv[1]) {

	case 'housekeeping':
		try {
			$houseKeeping = new HouseKeeping($db, true);
			$houseKeeping->houseKeeping();
		} catch(Exception $e) {
			echo $argv[1].' ERROR: '.$e->getMessage()."\n";
			exit(1);
		}
		break;

	case 'ldapsync':
		try {
			$ldapSync = new LdapSync($db, true);
			$ldapSync->sync();
		} catch(Exception $e) {
			echo $argv[1].' ERROR: '.$e->getMessage()."\n";
			exit(1);
		}
		break;

	default:
		echo $argv[1].' ERROR: unknown command'."\n";
		exit(1);

}
