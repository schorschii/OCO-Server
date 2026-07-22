<?php

class MobileDeviceCommandController {

	private $apple;
	private $android;

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->apple = new Apple\AppleCommandController($db, $debug);
		$this->android = new Android\AndroidCommandController($db, $debug);
	}

	function syncAppsProfiles(array|null $deviceIds=null) {
		$success = true;
		$mds = $this->db->selectAllMobileDevice();
		foreach($mds as $md) {
			if(!empty($deviceIds) && !in_array($md->id, $deviceIds)) continue;

			if($md->getOsType() == Models\MobileDevice::OS_TYPE_IOS) {
				$this->apple->syncAppsProfiles($md);

			} elseif($md->getOsType() == Models\MobileDevice::OS_TYPE_ANDROID) {
				if(!$this->android->syncAppsProfiles($md)) {
					$success = false;
				}
			}
		}
		$this->apple->iosPush($deviceIds);
		return $success;
	}

}
