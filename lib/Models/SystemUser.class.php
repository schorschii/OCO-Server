<?php

namespace Models;

class SystemUser {

	private $db;

	public function __construct(\DatabaseController $db) {
		$this->db = $db;
	}

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

	// contextual permission check implementation
	private $pm;
	function checkPermission($ressource, String $method, Bool $throw=true) {
		if($this->pm === null) $this->pm = new \PermissionManager($this->db, $this);
		$checkResult = $this->pm->hasPermission($ressource, $method);
		if(!$checkResult && $throw) throw new \PermissionException();
		return $checkResult;
	}
	public function getPermissionEntry($ressource, String $method) {
		if($this->pm === null) $this->pm = new \PermissionManager($this->db, $this);
		return $this->pm->getPermissionEntry($ressource, $method);
	}

}
