<?php

namespace Models;

class MobileDevice {

	public $id;
	public $udid;
	public $state;
	public $device_name;
	public $serial;
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
	public $policy;
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
	function isOnline() {
		if($this->getOsType() == self::OS_TYPE_ANDROID) {
			return empty($this->state) ? false : $this->state != 'PROVISIONING';
		} elseif($this->getOsType() == self::OS_TYPE_IOS) {
			return !empty($this->udid);
		}
		return false;
	}
	function getDisplayName() {
		return $this->device_name ? $this->device_name : $this->serial;
	}
	function getMacAddresses() {
		$addrs = [];
		if($this->getOsType() === self::OS_TYPE_IOS) {
			$info = json_decode($this->info ?? '', true);
			if($info) {
				if(isset($info['WiFiMAC'])) $addrs[] = $info['WiFiMAC'];
				if(isset($info['BluetoothMAC'])) $addrs[] = $info['BluetoothMAC'];
			}
		} elseif($this->getOsType() === self::OS_TYPE_ANDROID) {
			$info = json_decode($this->info ?? '', true);
			if($info) {
				if(isset($info['networkInfo']) && !empty(isset($info['networkInfo']['wifiMacAddress'])))
					$addrs[] = $info['networkInfo']['wifiMacAddress'];
			}
		}
		return $addrs;
	}

}
