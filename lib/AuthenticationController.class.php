<?php

class AuthenticationController {

	/*
		 Class AuthenticationController
		 Handles Login Requests
	*/

	private $db;

	function __construct($db) {
		$this->db = $db;
	}

	/*** Authentication Logic ***/
	public function login($username, $password) {
		$user = $this->db->getSystemUserByLogin($username);
		if($user === null) {
			sleep(2); // delay to avoid brute force attacks
			throw new AuthenticationException(LANG('user_does_not_exist'));
		} else {
			if(!$user->locked) {
				if($this->checkPassword($user, $password)) {
					$this->db->updateSystemUserLastLogin($user->id);
					return $user;
				} else {
					sleep(2);
					throw new AuthenticationException(LANG('login_failed'));
				}
			} else {
				sleep(1);
				throw new AuthenticationException(LANG('user_locked'));
			}
		}
		return false;
	}
	private function checkPassword($userObject, $checkPassword) {
		$result = $this->validatePassword($userObject, $checkPassword);
		if(!$result) {
			// log for fail2ban
			error_log('user '.$userObject->username.': authentication failure');
		}
		return $result;
	}
	private function validatePassword($userObject, $checkPassword) {
		if($userObject->ldap) {
			if(empty($checkPassword)) return false;
			$ldapconn = ldap_connect(LDAP_SERVER);
			if(!$ldapconn) return false;
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 3);
			$ldapbind = @ldap_bind($ldapconn, $userObject->username.'@'.LDAP_DOMAIN, $checkPassword);
			if(!$ldapbind) return false;
			return true;
		} else {
			return password_verify($checkPassword, $userObject->password);
		}
	}

}
