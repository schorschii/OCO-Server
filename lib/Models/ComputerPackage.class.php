<?php

namespace Models;

class ComputerPackage {

	public $id;
	public $computer_id;
	public $package_id;
	public $installed_by;
	public $installed_procedure;
	public $installed;

	// joined computer attributes
	public $computer_hostname;

	// joined package attributes
	public $package_family_id;
	public $package_family_name;
	public $package_version;

}
