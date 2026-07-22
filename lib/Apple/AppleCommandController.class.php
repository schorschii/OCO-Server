<?php

namespace Apple;

class AppleCommandController extends \MobileDeviceCommandControllerBase {

	private $vpp;
	private $debug;

	function __construct(\DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->vpp = new VolumePurchaseProgram($db);
		$this->debug = $debug;
	}

	function syncAppsProfiles(\Models\MobileDevice $md) {
		$force = false;
		if($this->iosProfiles($md)) $force = true;
		if($this->iosAppInstalls($md)) $force = true;
		$this->iosInventoryJobs($md, $force);

		$syncTokens = $this->iosSyncTokens($md);
		if($syncTokens['DeclarationsToken'] != $md->policy) {
			$result = $this->db->insertMobileDeviceCommand($md->id, 'DeclarativeManagement', json_encode([
				'RequestType' => 'DeclarativeManagement',
				'Data' => base64_encode(json_encode([
					'SyncTokens' => $syncTokens
				])),
				'_data' => ['Data'],
			]));
			if($result) {
				$this->db->updateMobileDevice(
					$md->id, $md->udid, $md->state, $md->device_name, $md->serial, $md->vendor_description,
					$md->model, $md->os, $md->device_family, $md->color,
					$md->profile_uuid, $md->push_token, $md->push_magic, $md->push_sent,
					$md->unlock_token, $md->info, $syncTokens['DeclarationsToken']/*policy*/, $md->parameters, $md->notes, $md->force_update
				);
				echo('Created command for updating declarations on device '.$md->id."\n");
			}
		}
	}

	private function iosProfiles(\Models\MobileDevice $md) {
		$changed = false;
		// check if every assigned profile is installed, otherwise create command to install it
		$handledProfileUuids = [];
		$installedProfileUuids = $this->db->selectAllMobileDeviceProfileUuidByMobileDeviceId($md->id);
		foreach($this->getProfilesByMobileDeviceId($md->id) as $p) {
			if($p->type != \Models\Profile::TYPE_IOS) continue;
			$uuid = $p->getUuid();
			if(!$uuid) continue;
			if(in_array($uuid, $handledProfileUuids)) continue; // handle 1 profile only once if assigned via 2 groups
			if(!array_key_exists($uuid, $installedProfileUuids)) {
				// parse the plist into array and replace parameters
				$plist = new \CFPropertyList\CFPropertyList();
				$plist->parse($p->payload);
				$payload = $plist->toArray();
				if(!self::replacePlaceholders($payload, json_decode($md->parameters, true)??[])) {
					echo('Cannot install profile '.$p->name.' because a parameter is missing'."\n");
					continue;
				}
				// convert processed array back to plist
				$td = new \CFPropertyList\CFTypeDetector();
				$plist = new \CFPropertyList\CFPropertyList();
				$plist->add( $td->toCFType( $payload ) );
				$result = $this->db->insertMobileDeviceCommand($md->id, 'InstallProfile', json_encode([
					'RequestType' => 'InstallProfile',
					'Payload' => base64_encode($plist->toXML(true)),
					'_data' => ['Payload']
				]));
				if($result) {
					$changed = true;
					echo('Created command for installing profile '.$uuid.' on device '.$md->id."\n");
				}
			}
			// remove it from array - so we can determine which profiles needs to be removed in the next step
			unset($installedProfileUuids[$uuid]);
			$handledProfileUuids[] = $uuid;
		}
		// remove all left installed profiles
		foreach($installedProfileUuids as $uuid => $profile) {
			$requestPlist = new \CFPropertyList\CFPropertyList();
			$requestPlist->parse($profile->content);
			$profileValues = $requestPlist->toArray();

			if(!$profileValues) continue;
			if(!$profileValues['IsManaged']) continue; // do not try to uninstall profiles which are not managed by us
			foreach($profileValues['PayloadContent']??[] as $payload) { // do not uninstall MDM profiles
				if($payload['PayloadType'] == 'com.apple.mdm') continue 2;
			}
			$result = $this->db->insertMobileDeviceCommand($md->id, 'RemoveProfile', json_encode([
				'RequestType' => 'RemoveProfile',
				'Identifier' => $profile->identifier
			]));
			if($result) {
				$changed = true;
				echo('Created command for deleting profile '.$uuid.' on device '.$md->id."\n");
			}
		}
		return $changed;
	}

	private function iosAppInstalls(\Models\MobileDevice $md) {
		$changed = false;
		// check if every assigned app is installed, otherwise create job
		$installedApps = $this->db->selectAllMobileDeviceAppIdentifierByMobileDeviceId($md->id);
		foreach($this->getManagedAppsByMobileDeviceId($md->id) as $app) {
			if($app->type != \Models\ManagedApp::TYPE_IOS) continue;
			if(array_key_exists($app->identifier, $installedApps)) continue;

			// assign VPP license
			if($app->vpp_amount) {
				$this->vpp->associateAssets(
					[ [ 'adamId' => $app->store_id ] ],
					[], [ $md->serial ]
				);
			}
			// create install command
			$flagRemoveOnMdmRemove = $app->remove_on_mdm_remove ? 1 : 0;
			$flagPreventBackup = $app->disable_cloud_backup ? 4 : 0;
			$result = $this->db->insertMobileDeviceCommand($md->id, 'InstallApplication', json_encode([
				'RequestType' => 'InstallApplication',
				'iTunesStoreID' => $app->store_id,
				'ManagementFlags' => $flagRemoveOnMdmRemove + $flagPreventBackup,
				'Options' => [ 'PurchaseMethod' => ($app->vpp_amount ? 1 : 0) ],
				'InstallAsManaged' => true,
				'Attributes' => [ 'Removable' => boolval($app->removable) ],
				'Configuration' => ($app->config && json_decode($app->config, true)) ? json_decode($app->config, true) : []
			]));
			if($result) {
				$changed = true;
				echo('Created command for installing app '.$app->identifier.' on device '.$md->id."\n");
			}
		}
		return $changed;
	}

	private function iosInventoryJobs(\Models\MobileDevice $md, bool $force) {
		// check if device should update inventory data and if so, create a job command for that
		if(time() - strtotime($md->last_update??'') > intval($this->db->settings->get('agent-update-interval'))
		|| $md->info === null
		|| !empty($md->force_update)
		|| $force) {
			$hasUpdateJob = false;
			foreach($this->db->selectAllMobileDeviceCommandByMobileDevice($md->id) as $mdc) {
				if($mdc->name == MdmCommand::DEVICE_INFO['RequestType']
				&& $mdc->state == \Models\MobileDeviceCommand::STATE_QUEUED) {
					$hasUpdateJob = true;
					break;
				}
			}
			if(!$hasUpdateJob) {
				echo('Add mobile device info update job for device '.$md->id."\n");
				$this->db->insertMobileDeviceCommand($md->id, MdmCommand::DEVICE_INFO['RequestType'], json_encode(MdmCommand::DEVICE_INFO));
				$this->db->insertMobileDeviceCommand($md->id, MdmCommand::APPS_INFO['RequestType'], json_encode(MdmCommand::APPS_INFO));
				$this->db->insertMobileDeviceCommand($md->id, MdmCommand::PROFILE_INFO['RequestType'], json_encode(MdmCommand::PROFILE_INFO));
			}
		}
	}

	function iosPush(array|null $deviceIds=null) {
		// get which devices should be contacted via push
		$wakeMdIds = [];
		foreach($this->db->selectAllMobileDeviceCommand() as $mdc) {
			if($mdc->state != \Models\MobileDeviceCommand::STATE_QUEUED
			&& $mdc->state != \Models\MobileDeviceCommand::STATE_SENT)
				continue;
			if(in_array($mdc->mobile_device_id, $wakeMdIds))
				continue;
			$md = $this->db->selectMobileDevice($mdc->mobile_device_id);
			if($md->getOsType() != \Models\MobileDevice::OS_TYPE_IOS) continue;
			if(empty($md->push_sent) || time() - strtotime($md->push_sent) > 60*60) {
				$wakeMdIds[] = $md->id;
			}
		}
		// send push notification to selected devices
		if($wakeMdIds) {
			$ade = new AutomatedDeviceEnrollment($this->db);
			$apnCert = $ade->getMdmApnCert();
			$apn = new PushNotificationService($this->db, $ade->getMdmApnCert()['certinfo']['subject']['UID'], $apnCert['cert'], $apnCert['privkey']);
			foreach($wakeMdIds as $mdId) {
				if(!empty($deviceIds) && !in_array($mdId, $deviceIds)) continue;
				$md = $this->db->selectMobileDevice($mdId);
				if(empty($md->push_token) || empty($md->push_magic)) continue;
				echo('Sending push notification to device '.$md->id.' '.$md->serial."\n");
				$apn->send($md->push_token, $md->push_magic);
				$this->db->updateMobileDevice($md->id,
					$md->udid, $md->state, $md->device_name, $md->serial, $md->vendor_description,
					$md->model, $os??$md->os, $md->device_family, $md->color,
					$md->profile_uuid, $md->push_token, $md->push_magic, date('Y-m-d H:i:s'), $md->unlock_token,
					$md->info, $md->policy, $md->parameters, $md->notes, 0/*force_update*/
				);
			}
		}
	}

	function iosDeclarations(\Models\MobileDevice $md) {
		$declarations = [];
		foreach($this->getProfilesByMobileDeviceId($md->id) as $p) {
			if($p->type != \Models\Profile::TYPE_IOS_DECLARATION) continue;
			$declarations[] = $p;
		}
		return $declarations;
	}
	function iosSyncTokens(\Models\MobileDevice $md) {
		$lastUpdateTime = 0; $overallHash = '';
		foreach($this->iosDeclarations($md) as $p) {
			$overallHash .= $p->getToken();
			// ignore the timezone (= force to UTC) when comparing lastUpdate time because PHP timezone config for Apache and CLI (cron job) can differ!
			$lastUpdate = strtotime(($p->updated ?? $p->created).' UTC');
			if($lastUpdate > $lastUpdateTime) $lastUpdateTime = $lastUpdate;
		}
		return [
			'Timestamp' => date('Y-m-d\TH:i:s\Z', $lastUpdateTime),
			'DeclarationsToken' => md5($overallHash)
		];
	}

}
