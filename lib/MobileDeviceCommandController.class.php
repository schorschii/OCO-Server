<?php

class MobileDeviceCommandController {

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->debug = $debug;
	}

	function mdmCron() {
		// check if device should update inventory data
		foreach($this->db->selectAllMobileDevice() as $md) {
			if(time() - strtotime($md->last_update??'') > intval($this->db->settings->get('agent-update-interval'))
			|| $md->info === null
			|| !empty($md->force_update)) {
				$hasUpdateJob = false;
				foreach($this->db->selectAllMobileDeviceCommandByMobileDevice($md->id) as $mdc) {
					if($mdc->name == Apple\MdmCommand::DEVICE_INFO['RequestType']
					&& $mdc->state == Models\MobileDeviceCommand::STATE_QUEUED) {
						$hasUpdateJob = true;
						break;
					}
				}
				if(!$hasUpdateJob) {
					echo('Add mobile device info update job for '.$md->serial."\n");
					$this->db->insertMobileDeviceCommand($md->id, Apple\MdmCommand::DEVICE_INFO['RequestType'], json_encode(Apple\MdmCommand::DEVICE_INFO));
					$this->db->insertMobileDeviceCommand($md->id, Apple\MdmCommand::APPS_INFO['RequestType'], json_encode(Apple\MdmCommand::APPS_INFO));
				}
			}
		}
		// get which devices should be contacted
		$wakeMdIds = [];
		foreach($this->db->selectAllMobileDeviceCommand() as $mdc) {
			if($mdc->state == Models\MobileDeviceCommand::STATE_QUEUED
			&& !in_array($mdc->mobile_device_id, $wakeMdIds)) {
				$md = $this->db->selectMobileDevice($mdc->mobile_device_id);
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
				echo('Sending push notification to '.$md->serial."\n");
				$apn->send($md->push_token, $md->push_magic);
				$this->db->updateMobileDevice(
					$md->id, $md->udid, $md->device_name, $md->serial, $md->vendor_description,
					$md->model, $os??$md->os, $md->device_family, $md->color,
					$md->profile_uuid, $md->push_token, $md->push_magic, date('Y-m-d H:i:s'), $md->unlock_token,
					$md->info, $md->notes, $md->force_update
				);
			}
		}
	}

}
