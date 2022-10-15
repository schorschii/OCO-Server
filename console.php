#!/usr/bin/env php
<?php
if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

if(!isset($argv[1]))
	die('Please specify an action as first parameter.'."\n");

require_once(__DIR__.'/loader.inc.php');
$extensionMethods = $ext->getAggregatedConf('console-methods');

try {

	switch($argv[1]) {

		case 'housekeeping':
			$houseKeeping = new HouseKeeping($db, $ext, true);
			$houseKeeping->cleanup();
			break;

		case 'ldapsync':
			$ldapSync = new LdapSync($db, true);
			echo '<===== Syncing System Users =====>'."\n";
			$ldapSync->syncSystemUsers();
			echo '<===== Syncing Domain Users =====>'."\n";
			$ldapSync->syncDomainUsers();
			break;

		default:
			if(array_key_exists($argv[1], $extensionMethods)) {
				call_user_func($extensionMethods[$argv[1]], $db);
				die();
			}
			throw new Exception('unknown command');

	}

} catch(Exception $e) {
	echo $argv[1].' ERROR: '.$e->getMessage()."\n";
	exit(1);
}
