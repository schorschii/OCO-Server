<?php

namespace Models;

class MobileDevice {

	public $id;
	public $udid;
	public $device_name;
	public $serial_number;
	public $vendor_description;
	public $model;
	public $os;
	public $device_family;
	public $color;
	public $profile_uuid;
	public $push_token;
	public $push_magic;
	public $push_sent;
	public $unlock_token;
	public $info;
	public $notes;
	public $created;
	public $last_update;
	public $force_update;

	// constants
	const OS_TYPE_UNKNOWN = 0;
	const OS_TYPE_ANDROID = 4;
	const OS_TYPE_IOS     = 5;

	// functions
	function getOsType() {
		if(strpos($this->os, 'Android') !== false) return self::OS_TYPE_ANDROID;
		elseif(strpos($this->os, 'iOS') !== false) return self::OS_TYPE_IOS;
		else return self::OS_TYPE_UNKNOWN;
	}
	function getIcon() {
		$type = $this->getOsType();
		if($type == self::OS_TYPE_UNKNOWN) return 'img/mobile-device.dyn.svg';
		elseif($type == self::OS_TYPE_ANDROID) return 'img/mobile-device-android.dyn.svg';
		elseif($type == self::OS_TYPE_IOS) return 'img/mobile-device-ios.dyn.svg';
		else return '';
	}
	function isOnline($db) {
		if(!$db instanceof \DatabaseController) throw new Exception('Missing DatabaseController Reference');
		return time() - strtotime($this->last_update??0) < intval($db->settings->get('computer-offline-seconds'));
	}

}
