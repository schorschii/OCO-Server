<?php

namespace Models;

class DomainUser implements IUser {

	public $id;
	public $uid;
	public $domain;
	public $username;
	public $display_name;
	public $domain_user_role_id;
	public $password;
	public $ldap;
	public $last_login;
	public $created;

	// joined attributes
	public $domain_user_role_name;
	public $domain_user_role_permissions;

	// aggregated values
	public $timestamp;
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
	public function getRoleName() {
		return $this->domain_user_role_name;
	}
	public function getRolePermissions() {
		return $this->domain_user_role_permissions;
	}

}
