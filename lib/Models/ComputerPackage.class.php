<?php

namespace Models;

class ComputerPackage {

	public $id;
	public $computer_id;
	public $package_id;
	public $installed_procedure;
	public $installed_by_system_user_id;
	public $installed_by_domain_user_id;
	public $installed;

	// joined computer attributes
	public $computer_hostname;

	// joined package attributes
	public $package_family_id;
	public $package_family_name;
	public $package_version;

	// joined user attributes
	public $installed_by_system_user_username;
	public $installed_by_domain_user_username;

}
