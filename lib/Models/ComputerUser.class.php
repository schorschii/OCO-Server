<?php

namespace Models;

class ComputerUser extends ComputerPassword {

	public $id;
	public $computer_id;
	public $username;
	public $display_name;
	public $uid;
	public $gid;
	public $home;
	public $shell;
	public $disabled;

}
