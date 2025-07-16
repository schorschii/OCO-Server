<?php

namespace Models;

class ManagedApp {

	const TYPE_IOS = 'ios';
	const TYPE_ANDROID = 'android';

	const ANDROID_INSTALL_TYPES = [
		'PREINSTALLED' => 'preinstalled_deletable',
		'FORCE_INSTALLED' => 'force_installed',
		'BLOCKED' => 'blocked',
		'AVAILABLE' => 'available_for_install',
		'REQUIRED_FOR_SETUP' => 'required_for_setup',
		'KIOSK' => 'kiosk_mode',
	];

	public $id;
	public $identifier;
	public $store_id;
	public $name;
	public $vpp_amount;
	public $configurations;

	// joined attributes
	public $removable;
	public $disable_cloud_backup;
	public $remove_on_mdm_remove;
	public $install_type;
	public $config_id;
	public $config;

	function getConfigurations() {
		return json_decode($this->configurations??'{}', true);
	}

}
