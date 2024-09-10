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

		case 'mdmcron':
			$mdcc = new MobileDeviceCommandController($db, true);
			$mdcc->mdmCron();
			break;

		case 'applesync':
			$ade = new Apple\AutomatedDeviceEnrollment($db);
			$ade->syncDevices();
			break;

		case 'applepush':
			if(empty($argv[2])) throw new Exception('Please give the device serial number as second parameter!');
			$ade = new Apple\AutomatedDeviceEnrollment($db);
			$md = $db->selectMobileDeviceBySerialNumber($argv[2]);
			if(!$md) throw new InvalidRequestException('Serial number not found');
			$apnCert = $ade->getMdmApnCert();
			$apn = new Apple\PushNotificationService($db, $apnCert['certinfo']['subject']['UID'], $apnCert['cert'], $apnCert['privkey']);
			var_dump( $apn->send($md->push_token, $md->push_magic) );
			break;

		case 'ldapsync':
			$ldapSync = new LdapSync($db, true);
			echo '<===== Syncing System Users =====>'."\n";
			$ldapSync->syncSystemUsers();
			echo '<===== Syncing Domain Users =====>'."\n";
			$ldapSync->syncDomainUsers();
			break;

		case 'upgradeschema':
			$migrator = new DatabaseMigrationController($db->getDbHandle(), true);
			if($migrator->upgrade()) {
				echo 'Database schema upgraded successfully.'."\n";
			} else {
				echo 'Database schema is already up to date.'."\n";
			}
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
	echo $e->getTraceAsString();
	echo "\n";
	exit(1);
}
