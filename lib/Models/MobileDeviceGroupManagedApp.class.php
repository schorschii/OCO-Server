<?php

namespace Models;

class MobileDeviceGroupManagedApp {

	public $id;
	public $mobile_device_group_id;
	public $managed_app_id;
	public $removable;
	public $disable_cloud_backup;
	public $remove_on_mdm_remove;
	public $install_type;
	public $config_id;
	public $config;

	// joined attributes
	public $identifier;
	public $store_id;
	public $name;
	public $vpp_amount;

}
