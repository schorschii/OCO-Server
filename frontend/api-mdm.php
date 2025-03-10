<?php
require_once(__DIR__.'/../loader.inc.php');


$ade = new Apple\AutomatedDeviceEnrollment($db);

$db->insertLogEntry(Models\Log::LEVEL_DEBUG, null, null, Models\Log::ACTION_AGENT_API_RAW, file_get_contents('php://input'));

$path = $_SERVER['PATH_INFO'] ?? '';
if($path === '/profile') {

	if(empty($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/pkcs7-signature') {
		throw new RuntimeException('Invalid content-type');
	}

	// iPhone sends a request in form of a pkcs7 signed plist file
	/*<?xml version="1.0" encoding="UTF-8"?>
	<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
	<plist version="1.0">
	<dict>
		<key>LANGUAGE</key>
		<string>de-DE</string>
		<key>PRODUCT</key>
		<string>iPhone10,1</string>
		<key>SERIAL</key>
		<string>000000000000</string>
		<key>UDID</key>
		<string>0000000000000000000000000000000000000000</string>
		<key>VERSION</key>
		<string>20H343</string>
	</dict>
	</plist>*/
	$body = file_get_contents('php://input');
	$tmpOutFileSmime = '/tmp/iphone-request.p7m';
	$tmpOutFileData = '/tmp/iphone-request.plist';
	file_put_contents($tmpOutFileSmime,
		'MIME-Version: 1.0'."\n"
		.'Content-Disposition: attachment; filename="smime.p7m"'."\n"
		.'Content-Type: application/x-pkcs7-mime; smime-type=signed-data; name="smime.p7m"'."\n"
		.'Content-Transfer-Encoding: base64'."\n"
		."\n"
		.chunk_split(base64_encode($body))
	);
	// iPhone CA cert is expired, so we need to use PKCS7_NOVERIFY until somebody finds a better solution
	if(openssl_pkcs7_verify($tmpOutFileSmime, PKCS7_NOVERIFY, null, [], null, $tmpOutFileData) !== true) {
		throw new RuntimeException('Unable to parse pkcs7 signed plist');
	}
	$requestPlist = new CFPropertyList\CFPropertyList($tmpOutFileData);
	$request = $requestPlist->toArray();

	// store the UDID
	if(empty($request['UDID']) || empty($request['SERIAL'])) {
		throw new InvalidRequestException('At least one required parameter is missing');
	}
	$md = $db->selectMobileDeviceBySerialNumber($request['SERIAL']);
	if(!$md) throw new NotFoundException();
	$db->updateMobileDevice(
		$md->id, $request['UDID'], $md->state, $md->device_name, $md->serial, $md->vendor_description,
		$md->model, $md->os, $md->device_family, $md->color,
		$md->profile_uuid, $md->push_token, $md->push_magic, $md->push_sent,
		$md->unlock_token, $md->info, $md->policy, $md->notes, $md->force_update, true/*last_update*/
	);

	// deliver the enrollment profile to the device
	echo $ade->generateEnrollmentProfile($request['SERIAL']);


} elseif($path === '/checkin') {

	if(empty($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/x-apple-aspen-mdm-checkin') {
		throw new RuntimeException('Invalid content-type');
	}

	$body = file_get_contents('php://input');
	checkSignature($body, $_SERVER['HTTP_MDM_SIGNATURE']??null);
	$requestPlist = new CFPropertyList\CFPropertyList();
	$requestPlist->parse($body);
	$request = $requestPlist->toArray();

	switch($request['MessageType'] ?? null) {
		case 'Authenticate':
			/*<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
			<plist version="1.0">
			<dict>
				<key>BuildVersion</key>
				<string>20H343</string>
				<key>MessageType</key>
				<string>Authenticate</string>
				<key>OSVersion</key>
				<string>16.7.8</string>
				<key>ProductName</key>
				<string>iPhone10,1</string>
				<key>SerialNumber</key>
				<string>000000000000</string>
				<key>Topic</key>
				<string>com.apple.mgmt.External.00000000-0000-0000-0000-000000000000</string>
				<key>UDID</key>
				<string>0000000000000000000000000000000000000000</string>
			</dict>
			</plist>*/
			if(empty($request['UDID']) || empty($request['SerialNumber'])) {
				throw new InvalidRequestException('At least one required parameter is missing');
			}
			$os = $request['OSVersion'] ? 'iOS '.$request['OSVersion'] : null;
			$md = $db->selectMobileDeviceBySerialNumber($request['SerialNumber']);
			if(!$md) {
				throw new InvalidRequestException('Device not found');
			}
			// device already registered - store the UDID
			$db->updateMobileDevice(
				$md->id, $request['UDID'], $md->state, $md->device_name, $md->serial, $md->vendor_description,
				$md->model, $os??$md->os, $md->device_family, $md->color,
				$md->profile_uuid, $md->push_token, $md->push_magic, $md->push_sent,
				$md->unlock_token, $md->info, $md->policy, $md->notes, $md->force_update, true/*last_update*/
			);
			break;

		case 'TokenUpdate':
			/*<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
			<plist version="1.0">
			<dict>
				<key>AwaitingConfiguration</key>
				<false/>
				<key>MessageType</key>
				<string>TokenUpdate</string>
				<key>PushMagic</key>
				<string>00000000-0000-0000-0000-000000000000</string>
				<key>Token</key>
				<data>
				00000000000000000000000000000000000000000000
				</data>
				<key>Topic</key>
				<string>com.apple.mgmt.External.00000000-0000-0000-0000-000000000000</string>
				<key>UDID</key>
				<string>0000000000000000000000000000000000000000</string>
				<key>UnlockToken</key>
				<data>
				0000000000000000000000000000000000...
				</data>
			</dict>
			</plist>*/
			if(empty($request['UDID']) || empty($request['Token'] || empty($request['PushMagic']))) {
				throw new InvalidRequestException('At least one required parameter is missing');
			}
			$md = $db->selectMobileDeviceByUdid($request['UDID']);
			if(!$md) throw new NotFoundException();
			$db->updateMobileDevice(
				$md->id, $md->udid, $md->state, $md->device_name, $md->serial, $md->vendor_description,
				$md->model, $md->os, $md->device_family, $md->color,
				$md->profile_uuid, $request['Token'], $request['PushMagic'], $md->push_sent,
				$request['UnlockToken'], $md->info, $md->policy, $md->notes, $md->force_update, true/*last_update*/
			);
			break;

		case 'CheckOut':
			/*<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
			<plist version="1.0">
			<dict>
				<key>MessageType</key>
				<string>CheckOut</string>
				<key>Topic</key>
				<string>com.apple.mgmt.External.00000000-0000-0000-0000-000000000000</string>
				<key>UDID</key>
				<string>0000000000000000000000000000000000000000</string>
				</dict>
			</plist>*/
			if(empty($request['UDID'])) {
				throw new InvalidRequestException('At least one required parameter is missing');
			}
			$md = $db->selectMobileDeviceByUdid($request['UDID']);
			if(!$md) throw new NotFoundException();
			$db->updateMobileDevice(
				$md->id, null/*udid*/, $md->state, $md->device_name, $md->serial, $md->vendor_description,
				$md->model, $md->os, $md->device_family, $md->color,
				$md->profile_uuid, null/*push_token*/, null/*push_magic*/, null/*push_sent*/,
				null/*unlock_token*/, $md->info, $md->policy, $md->notes, $md->force_update, true/*last_update*/
			);
			break;

		default:
			throw new RuntimeException('Unknown message type');
	}


} elseif($path === '/mdm') {

	if(empty($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/x-apple-aspen-mdm') {
		throw new RuntimeException('Invalid content-type');
	}

	/*<?xml version="1.0" encoding="UTF-8"?>
	<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
	<plist version="1.0">
		<dict>
			<key>Status</key>
			<string>Idle</string>
			<key>UDID</key>
			<string>0000000000000000000000000000000000000000</string>
		</dict>
	</plist>*/
	$body = file_get_contents('php://input');
	checkSignature($body, $_SERVER['HTTP_MDM_SIGNATURE']??null);
	$td = new \CFPropertyList\CFTypeDetector();
	$requestPlist = new CFPropertyList\CFPropertyList();
	$requestPlist->parse($body);
	$request = $requestPlist->toArray();
	if(empty($request['UDID'])) {
		throw new InvalidRequestException('UDID is missing');
	}
	$md = $db->selectMobileDeviceByUdid($request['UDID']);
	if(!$md) throw new NotFoundException();

	// store info about previous command result
	if(isset($request['Status']) && isset($request['CommandUUID'])) {
		$state = null;
		if($request['Status'] == 'Acknowledged')
			$state = Models\MobileDeviceCommand::STATE_SUCCESS;
		else
			$state = Models\MobileDeviceCommand::STATE_FAILED;

		$mdc = $db->selectMobileDeviceCommand($request['CommandUUID']);
		if(!$mdc) throw new Exception('Unknown command UUID');
		$db->updateMobileDeviceCommand($request['CommandUUID'], $mdc->mobile_device_id, $mdc->name, $mdc->parameter, $state, $body, date('Y-m-d H:i:s'));
		$rt = json_decode($mdc->parameter, true)['RequestType'] ?? '';

		// reset push_sent timestamp
		$db->updateMobileDevice(
			$md->id, $md->udid, $md->state, $md->device_name, $md->serial,
			$md->vendor_description, $md->model, $md->os, $md->device_family, $md->color,
			$md->profile_uuid, $md->push_token, $md->push_magic, null/*push_sent*/,
			$md->unlock_token, $md->info, $md->policy, $md->notes, $md->force_update
		);

		// store device info
		if($rt === 'DeviceInformation') {
			$os = $request['QueryResponses']['OSVersion'] ? 'iOS '.$request['QueryResponses']['OSVersion'] : null;
			$product = $request['QueryResponses']['ProductName'] ? $request['QueryResponses']['ProductName'] : null;
			$db->updateMobileDevice(
				$md->id, $md->udid, $md->state,
				$request['QueryResponses']['DeviceName']??'?',
				$request['QueryResponses']['SerialNumber']??$md->serial,
				$md->vendor_description, $product??$md->model, $os??$md->os, $md->device_family, $md->color,
				$md->profile_uuid, $md->push_token, $md->push_magic, null/*push_sent*/,
				$md->unlock_token, json_encode($request['QueryResponses']), $md->policy, $md->notes, $md->force_update, true/*last_update*/
			);
		} elseif($rt === 'InstalledApplicationList') {
			$apps = [];
			foreach($request['InstalledApplicationList']??[] as $app) {
				if(empty($app['Identifier']) || empty($app['Name']) || empty($app['Version']) || empty($app['ShortVersion'])) continue;
				$apps[] = [
					'identifier' => $app['Identifier'],
					'name' => $app['Name'],
					'display_version' => $app['ShortVersion'],
					'version' => $app['Version'],
				];
			}
			$db->updateMobileDeviceApps($md->id, $apps);
		} elseif($rt === 'ProfileList') {
			$profiles = [];
			foreach($request['ProfileList']??[] as $profile) {
				if(empty($profile['PayloadUUID']) || empty($profile['PayloadIdentifier'])) continue;
				$plist = new \CFPropertyList\CFPropertyList();
				$plist->add( $td->toCFType( $profile ) );
				$profiles[] = [
					'uuid' => $profile['PayloadUUID'],
					'identifier' => $profile['PayloadIdentifier'],
					'display_name' => $profile['PayloadDisplayName'] ?? '',
					'version' => $profile['PayloadVersion'] ?? '0',
					'content' => $plist->toXML(true),
				];
			}
			$db->updateMobileDeviceProfiles($md->id, $profiles);
		}
	}

	// send next queued command
	// https://developer.apple.com/documentation/devicemanagement/commands_and_queries
	foreach($db->selectAllMobileDeviceCommandByMobileDevice($md->id) as $mdc) {
		if($mdc->state == Models\MobileDeviceCommand::STATE_QUEUED) {
			// encode data parameter
			$parameter = json_decode($mdc->parameter, true);
			if(isset($parameter['_data']) && is_array($parameter['_data'])) {
				foreach($parameter['_data'] as $datap) {
					if(isset($parameter[$datap])) {
						$parameter[$datap] = new \CFPropertyList\CFData(base64_decode($parameter[$datap]));
					}
				}
				unset($parameter['_data']);
			}

			// compile command plist
			$plist = new \CFPropertyList\CFPropertyList();
			$plist->add( $td->toCFType( [
				'Command' => $parameter,
				'CommandUUID' => new \CFPropertyList\CFString($mdc->id),
			] ) );
			echo $plist->toXML(true);
			$db->updateMobileDeviceCommand(
				$mdc->id, $mdc->mobile_device_id, $mdc->name, $mdc->parameter,
				Models\MobileDeviceCommand::STATE_SENT, '', date('Y-m-d H:i:s')
			);

			break; // only 1 command per request!
		}
	}

} elseif(!empty($_GET['enterpriseToken'])) {

	$enterprise = null;
	$ae = new Android\AndroidEnrollment($db);
	try {
		$enterprise = $ae->getEnterprise();
	} catch(RuntimeException $e) {}
	if(!$enterprise) {
		$ae->createEnterprise($_GET['enterpriseToken']);
		header('Location: index.php?view=settings-mdm');
		die();
	} else echo 'Enterprise already set!';

} else {

	header('HTTP/1.1 400 INVALID REQUEST');
	die();

}


function checkSignature(string $body, string $signature) {
	global $ade;
	if(empty($signature)) throw new Exception('Signature missing');

	$processId   = uniqid();
	$tmpFileBody = '/tmp/mdm-payload-'.$processId;
	$tmpFileCa   = '/tmp/mdm-ca-'.$processId;
	file_put_contents($tmpFileBody, $body);
	file_put_contents($tmpFileCa, $ade->getMdmDeviceCaCert());

	// TODO: check CN === $md->id

	// it seems not possible to verify detached signatures using PHP's openssl_cms_verify(),
	// that's why we use the command line utility
	$process = proc_open(
		'/usr/bin/openssl cms -verify -binary -inform DER -in - -content '.escapeshellarg($tmpFileBody).' -CAfile '.escapeshellarg($tmpFileCa),
		array(
			0 => array('pipe', 'r'), // STDIN
			1 => array('pipe', 'w'), // STDOUT
			2 => array('pipe', 'w'), // STDERR
		),
		$pipes, null, [/*env*/]
	);
	if(!is_resource($process)) throw new Exception('Unable to start openssl verification process');

	fwrite($pipes[0], base64_decode($signature)); fclose($pipes[0]);
	$stdOut = stream_get_contents($pipes[1]); fclose($pipes[1]);
	$stdErr = stream_get_contents($pipes[2]); fclose($pipes[2]);

	$returnCode = proc_close($process);
	if($returnCode != 0) throw new Exception('Signature verification failed: '.$returnCode."\n".$stdErr);
}
