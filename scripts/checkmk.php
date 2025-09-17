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
		printCheckResult('2', 'OCO License', '-', 'License is invalid!');
	} elseif(!$license->isFree() && $license->getRemainingTime() < 60*60*24*14) {
		$remainingDays = round($license->getRemainingTime() / (60*60*24));
		printCheckResult('1', 'OCO License', '-', 'Expires in '.$remainingDays.' day(s)');
	} else {
		printCheckResult('0', 'OCO License', '-', 'Valid until '.date('Y-m-d', $license->getExpireTime()));
	}

	// check last cron run
	$lastCronRun = strtotime($db->selectSettingByKey('cron-executed')->value ?? '1970-01-01 00:00:00');
	if($lastCronRun > (time() - 60*11)) {
		printCheckResult('0', 'Cron Execution', '-', 'Housekeeping cron job is running');
	} else {
		printCheckResult('2', 'Cron Execution', '-', 'Housekeeping cron job last execution was on '.date('Y-m-d H:i:s', $lastCronRun));
	}

	// check MDM cert & token expiration
	$ade = new Apple\AutomatedDeviceEnrollment($db);
	$vpp = new Apple\VolumePurchaseProgram($db);
	try {
		$mdmApnCertInfo = openssl_x509_parse( $ade->getMdmApnCert()['cert'] );
		$mdmApnCertExpiry = intval($mdmApnCertInfo['validTo_time_t']);
		if($mdmApnCertExpiry - time() < 0) {
			printCheckResult('2', 'Apple MDM APN Cert', '-', 'Expired '.date('Y-m-d', $mdmApnCertExpiry));
		} elseif($mdmApnCertExpiry - time() > 60*60*24*14) {
			printCheckResult('0', 'Apple MDM APN Cert', '-', 'Valid until '.date('Y-m-d', $mdmApnCertExpiry));
		} else {
			printCheckResult('1', 'Apple MDM APN Cert', '-', 'Expires in '.date('Y-m-d', $mdmApnCertExpiry));
		}
	} catch(RuntimeException $e) {
		// probably no cert configured - ignoring
	}
	try {
		$mdmServerToken = $ade->getMdmServerToken();
		$mdmServerTokenExpiry = strtotime($mdmServerToken['access_token_expiry']);
		if($mdmServerTokenExpiry - time() < 0) {
			printCheckResult('2', 'Apple MDM Server Token', '-', 'Expired '.date('Y-m-d', $mdmServerTokenExpiry));
		} elseif($mdmServerTokenExpiry - time() > 60*60*24*14) {
			printCheckResult('0', 'Apple MDM Server Token', '-', 'Valid until '.date('Y-m-d', $mdmServerTokenExpiry));
		} else {
			printCheckResult('1', 'Apple MDM Server Token', '-', 'Expires in '.date('Y-m-d', $mdmServerTokenExpiry));
		}
	} catch(RuntimeException $e) {
		// probably no token configured - ignoring
	}
	try {
		$vppToken = $vpp->getToken();
		$vppTokenExpiry = strtotime($vppToken['expDate']);
		if($vppTokenExpiry - time() < 0) {
			printCheckResult('2', 'Apple MDM VPP Token', '-', 'Expired '.date('Y-m-d', $vppTokenExpiry));
		} elseif($vppTokenExpiry - time() > 60*60*24*14) {
			printCheckResult('0', 'Apple MDM VPP Token', '-', 'Valid until '.date('Y-m-d', $vppTokenExpiry));
		} else {
			printCheckResult('1', 'Apple MDM VPP Token', '-', 'Expires in '.date('Y-m-d', $vppTokenExpiry));
		}
	} catch(RuntimeException $e) {
		// probably no token configured - ignoring
	}

} catch(Exception $e) {
	echo $argv[1].' ERROR: '.$e->getMessage()."\n";
	echo $e->getTraceAsString();
	echo "\n";
	exit(1);
}


function printCheckResult($status, $name, $perfData, $description) {
	echo $status.' "'.$name.'" '.$perfData.' '.$description . "\n";
}
