<?php

namespace Models;

class DomainUser {

	public $id;
	public $username;
	public $display_name;

	// aggregated values
	public $logon_amount;
	public $computer_amount;

	// functions
	public function displayNameWithUsername() {
		if(empty($this->display_name)) {
			return $this->username;
		} else {
			return $this->display_name.' ('.$this->username.')';
		}
	}

}
