<?php

class MobileDeviceCommandController {

	private $db;
	private $vpp;
	private $ae;
	private $debug;

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->vpp = new Apple\VolumePurchaseProgram($db);
		$this->ae = new Android\AndroidEnrollment($db);
		$this->debug = $debug;
	}

	function mdmCron(array|null $deviceIds=null) {
		$mds = $this->db->selectAllMobileDevice();
		foreach($mds as $md) {
			if(!empty($deviceIds) && !in_array($md->id, $deviceIds)) continue;

			if($md->getOsType() == Models\MobileDevice::OS_TYPE_IOS) {
				$force = false;
				if($this->iosProfiles($md)) $force = true;
				if($this->iosAppInstalls($md)) $force = true;
				$this->iosInventoryJobs($md, $force);

			} elseif($md->getOsType() == Models\MobileDevice::OS_TYPE_ANDROID) {
				$appPolicy = $this->androidAppInstalls($md);
				$generalPolicy = $this->androidPolicies($md);
				$this->androidPoliciesPatch($md, array_merge($appPolicy, $generalPolicy));
			}
		}
		$this->iosPush();
	}

	private function androidAppInstalls(Models\MobileDevice $md) {
		// check if every assigned app is installed, otherwise add to policy
		$policy = [];
		foreach($this->db->selectAllManagedAppByMobileDeviceId($md->id) as $app) {
			if($app->type != Models\ManagedApp::TYPE_ANDROID) continue;

			if(empty($policy)) $policy['applications'] = [];

			$policy['applications'][] = [
				'packageName' => $app->identifier,
				'installType' => $app->install_type,
				'managedConfiguration' => json_decode($app->config, true),
			];
		}
		return $policy;
	}

	private function androidPolicies(Models\MobileDevice $md) {
		// check if every assigned policy is applied, otherwise add to policy
		$policy = [];
		foreach($this->db->selectAllProfileByMobileDeviceId($md->id) as $p) {
			if($p->type != Models\Profile::TYPE_ANDROID) continue;

			// check valid JSON
			$policyValues = json_decode($p->payload, true);
			if(!$policyValues || !is_array($policyValues)) continue;

			// merge policy values
			$policy = array_merge($policy, $policyValues);
		}
		return $policy;
	}

	private function androidPoliciesPatch(Models\MobileDevice $md, array $policyValues) {
		$appCount = count($policyValues['applications']??[]);
		$policyCount = count($policyValues);
		$newPolicy = json_encode($policyValues);

		// sync policy with Google API if changed
		if($newPolicy != $md->policy) {
			try {
				$this->ae->patchPolicy(strval($md->udid), $policyValues, strval($md->udid));
				echo('Updated policy for device '.$md->udid.' ('.$appCount.' apps, '.$policyCount.' policies)'."\n");
				$this->db->updateMobileDevice($md->id,
					$md->udid, $md->state, $md->device_name, $md->serial, $md->vendor_description,
					$md->model, $os??$md->os, $md->device_family, $md->color,
					$md->profile_uuid, $md->push_token, $md->push_magic, $md->push_sent, $md->unlock_token,
					$md->info, $newPolicy, $md->notes, $md->force_update
				);
			} catch(Exception $e) {
				echo('Error updating policy for device '.$md->udid.': '.$e->getMessage()."\n");
			}
		}
	}

	private function iosProfiles(Models\MobileDevice $md) {
		// check if every assigned profile is installed, otherwise create command
		$changed = false;
		$installedProfileUuids = $this->db->selectAllMobileDeviceProfileUuidByMobileDeviceId($md->id);
		foreach($this->db->selectAllProfileByMobileDeviceId($md->id) as $p) {
			if($p->type != Models\Profile::TYPE_IOS) continue;
			$uuid = $p->getUuid();
			if(!$uuid) continue;
			if(!array_key_exists($uuid, $installedProfileUuids)) {
				$result = $this->db->insertMobileDeviceCommand($md->id, 'InstallProfile', json_encode([
					'RequestType' => 'InstallProfile',
					'Payload' => base64_encode($p->payload),
					'_data' => ['Payload']
				]));
				if($result) {
					$changed = true;
					echo('Created command for installing profile '.$uuid.' on device '.$md->id."\n");
				}
			}
			unset($installedProfileUuids[$uuid]);
		}
		foreach($installedProfileUuids as $uuid => $profile) {
			$requestPlist = new CFPropertyList\CFPropertyList();
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

	private function iosAppInstalls(Models\MobileDevice $md) {
		// check if every assigned app is installed, otherwise create job
		$changed = false;
		$installedApps = $this->db->selectAllMobileDeviceAppIdentifierByMobileDeviceId($md->id);
		foreach($this->db->selectAllManagedAppByMobileDeviceId($md->id) as $app) {
			if($app->type != Models\ManagedApp::TYPE_IOS) continue;
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

	private function iosInventoryJobs(Models\MobileDevice $md, bool $force) {
		// check if device should update inventory data and if so, create a job command for that
		if(time() - strtotime($md->last_update??'') > intval($this->db->settings->get('agent-update-interval'))
		|| $md->info === null
		|| !empty($md->force_update)
		|| $force) {
			$hasUpdateJob = false;
			foreach($this->db->selectAllMobileDeviceCommandByMobileDevice($md->id) as $mdc) {
				if($mdc->name == Apple\MdmCommand::DEVICE_INFO['RequestType']
				&& $mdc->state == Models\MobileDeviceCommand::STATE_QUEUED) {
					$hasUpdateJob = true;
					break;
				}
			}
			if(!$hasUpdateJob) {
				echo('Add mobile device info update job for device '.$md->id."\n");
				$this->db->insertMobileDeviceCommand($md->id, Apple\MdmCommand::DEVICE_INFO['RequestType'], json_encode(Apple\MdmCommand::DEVICE_INFO));
				$this->db->insertMobileDeviceCommand($md->id, Apple\MdmCommand::APPS_INFO['RequestType'], json_encode(Apple\MdmCommand::APPS_INFO));
				$this->db->insertMobileDeviceCommand($md->id, Apple\MdmCommand::PROFILE_INFO['RequestType'], json_encode(Apple\MdmCommand::PROFILE_INFO));
			}
		}
	}

	private function iosPush() {
		// get which devices should be contacted via push
		$wakeMdIds = [];
		foreach($this->db->selectAllMobileDeviceCommand() as $mdc) {
			if($mdc->state == Models\MobileDeviceCommand::STATE_QUEUED
			&& !in_array($mdc->mobile_device_id, $wakeMdIds)) {
				$md = $this->db->selectMobileDevice($mdc->mobile_device_id);
				if($md->getOsType() != Models\MobileDevice::OS_TYPE_IOS) continue;
				if(empty($md->push_sent) || time() - strtotime($md->push_sent) > 60*60) {
					$wakeMdIds[] = $md->id;
				}
			}
		}
		// send push notification to selected devices
		if($wakeMdIds) {
			$ade = new Apple\AutomatedDeviceEnrollment($this->db);
			$apnCert = $ade->getMdmApnCert();
			$apn = new Apple\PushNotificationService($this->db, $ade->getMdmApnCert()['certinfo']['subject']['UID'], $apnCert['cert'], $apnCert['privkey']);
			foreach($wakeMdIds as $mdId) {
				$md = $this->db->selectMobileDevice($mdId);
				if(empty($md->push_token) || empty($md->push_magic)) continue;
				echo('Sending push notification to device '.$md->id.' '.$md->serial."\n");
				$apn->send($md->push_token, $md->push_magic);
				$this->db->updateMobileDevice($md->id,
					$md->udid, $md->state, $md->device_name, $md->serial, $md->vendor_description,
					$md->model, $os??$md->os, $md->device_family, $md->color,
					$md->profile_uuid, $md->push_token, $md->push_magic, date('Y-m-d H:i:s'), $md->unlock_token,
					$md->info, $md->policy, $md->notes, 0/*force_update*/
				);
			}
		}
	}

}
