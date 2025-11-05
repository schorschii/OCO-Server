#!/usr/bin/env php
<?php
if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

if(!isset($argv[1]))
	die('Please specify an action as first parameter.'."\n");

require_once(__DIR__.'/../loader.inc.php');

$ade = new Android\AndroidEnrollment($db);

try {

	switch($argv[1]) {

		case 'enterprises':
			var_dump(
				$ade->apiCall('GET', $ade::ANDROID_MANAGEMENT_API_URL.'/enterprises?'.http_build_query([
					'projectId' => $ade->getOAuthCredentials()['project_id']
				]), null)
			);
			die();

		case 'delete-enterprise':
			var_dump(
				$ade->apiCall('DELETE', $ade::ANDROID_MANAGEMENT_API_URL.'/enterprises/'.urlencode($argv[2]), null)
			);
			die();

		case 'issue-command':
			$enterpriseName = $ade->getEnterprise()['name'];
			var_dump(
				$ade->apiCall('POST', $ade::ANDROID_MANAGEMENT_API_URL.'/'.$enterpriseName.'/devices/'.urlencode($argv[2]).':issueCommand', json_encode($argv[3]))
			);

		case 'generate-enrollment-token':
			echo(
				$ade->generateEnrollmentToken($argv[2])
			);
			die();

		case 'delete-enrollment-token':
			$enterpriseName = $ade->getEnterprise()['name'];
			var_dump(
				$ade->apiCall('DELETE', $ade::ANDROID_MANAGEMENT_API_URL.'/'.$enterpriseName.'/enrollmentTokens/'.urlencode($argv[2]), null)
			);
			die();

		case 'enrollment-tokens':
			$enterpriseName = $ade->getEnterprise()['name'];
			var_dump(
				$ade->apiCall('GET', $ade::ANDROID_MANAGEMENT_API_URL.'/'.$enterpriseName.'/enrollmentTokens', null)
			);
			die();

		default:
			throw new Exception('unknown command');

	}

} catch(Exception $e) {
	echo $argv[1].' ERROR: '.$e->getMessage()."\n";
	echo $e->getTraceAsString();
	echo "\n";
	exit(1);
}
