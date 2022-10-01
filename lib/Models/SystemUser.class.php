<?php

namespace Models;

class SystemUser {

	// attributes
	public $id;
	public $uid;
	public $username;
	public $display_name;
	public $password;
	public $ldap;
	public $email;
	public $phone;
	public $mobile;
	public $description;
	public $locked;
	public $last_login;
	public $created;
	public $system_user_role_id;

	// joined system user role attributes
	public $system_user_role_name;
	public $system_user_role_permissions;

}
