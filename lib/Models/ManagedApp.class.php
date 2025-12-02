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
	const ANDROID_DELEGATED_SCOPES = [
		'CERT_INSTALL' => 'cert_installation',
		'CERT_SELECTION' => 'cert_selection',
		'MANAGED_CONFIGURATIONS' => 'managed_config',
		'BLOCK_UNINSTALL' => 'block_uninstall',
		'PERMISSION_GRANT' => 'permission_policy_and_grant_state',
		'PACKAGE_ACCESS' => 'package_access',
		'ENABLE_SYSTEM_APP' => 'enable_system_apps',
		'NETWORK_ACTIVITY_LOGS' => 'access_network_activity_logs',
		'SECURITY_LOGS' => 'access_security_logs',
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
