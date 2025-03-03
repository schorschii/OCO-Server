<?php

namespace Models;

class PasswordRotationRule {

	public $id;
	public $computer_group_id;
	public $username;
	public $alphabet;
	public $length;
	public $history;

	// joined attributes
	public $computer_group_name;

}
