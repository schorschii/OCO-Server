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
		$user = $this->db->selectSystemUserByUsername($username);
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
			// do not allow anonymous binds
			if(empty($checkPassword)) return false;

			$ldapServers = json_decode($this->db->selectSettingByKey('system-user-ldapsync'), true);
			if(empty($ldapServers) || !is_array($ldapServers)) {
				throw new Exception('System User LDAP sync not configured!');
			}

			foreach($ldapServers as $address => $details) {
				$username = $userObject->username;

				// get DN for LDAP auth check if configured
				$binddnQuery = empty($details['login-binddn-query']) ? '(&(objectClass=user)(samaccountname=%s))' : $details['login-binddn-query'];
				if($binddnQuery) {
					$ldapconn1 = ldap_connect($address);
					if(!$ldapconn1) continue;
					ldap_set_option($ldapconn1, LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option($ldapconn1, LDAP_OPT_NETWORK_TIMEOUT, 3);
					$ldapbind = ldap_bind($ldapconn1, $details['username'], $details['password']);
					if(!$ldapbind) continue;
					$result = ldap_search($ldapconn1, $details['query-root'], str_replace('%s', ldap_escape($userObject->username), $binddnQuery), ['dn']);
					if(!$result) continue;
					$data = ldap_get_entries($ldapconn1, $result);
					if(!empty($data[0]['dn'])) $username = $data[0]['dn'];
				}

				// try user authentication
				$ldapconn2 = ldap_connect($address);
				if(!$ldapconn2) continue;
				ldap_set_option($ldapconn2, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ldapconn2, LDAP_OPT_NETWORK_TIMEOUT, 3);
				$ldapbind = @ldap_bind($ldapconn2, $username, $checkPassword);
				if($ldapbind) return true;
			}

			return false;
		} else {
			return password_verify($checkPassword, $userObject->password);
		}
	}

}
