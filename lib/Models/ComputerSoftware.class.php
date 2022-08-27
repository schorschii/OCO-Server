<?php

namespace Models;

class ComputerSoftware {

	public $id;
	public $computer_id;
	public $software_id;
	public $installed;

	// joined software attributes
	public $software_name;
	public $software_version;
	public $software_description;

}
