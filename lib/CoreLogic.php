<?php

class CoreLogic {

	/*
		 Class CoreLogic
		 Database Abstraction Layer Wrapper

		 Adds additional checks & logic before the database is accessed and sanitizes user input.
		 It's public functions are used by the web frontend and the client API.
	*/

	private $db;

	function __construct($db) {
		$this->db = $db;
	}

	/*** Authentication Logic ***/
	public function login($username, $password) {
		$user = $this->db->getSystemuserByLogin($username);
		if($user === null) {
			sleep(2); // delay to avoid brute force attacks
			throw new Exception(LANG['user_does_not_exist']);
		} else {
			if(!$user->locked) {
				if($this->checkPassword($user, $password)) {
					return $user;
				} else {
					sleep(2);
					throw new Exception(LANG['login_failed']);
				}
			} else {
				sleep(1);
				throw new Exception(LANG['user_locked']);
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

	/*** Computer Operations ***/
	public function createComputer($hostname, $notes='') {
		$finalHostname = trim($hostname);
		if(empty($finalHostname)) {
			throw new Exception(LANG['hostname_cannot_be_empty']);
		}
		if($this->db->getComputerByName($finalHostname) !== null) {
			throw new Exception(LANG['hostname_already_exists']);
		}
		$result = $this->db->addComputer($finalHostname, ''/*Agent Version*/, []/*Networks*/, $notes, ''/*Agent Key*/, ''/*Server Key*/);
		if(!$result) throw new Exception(LANG['unknown_error']);
		return $result;
	}
	public function wolComputers($ids, $debugOutput=true) {
		$wolMacAdresses = [];
		foreach($ids as $id) {
			$c = $this->db->getComputer($id);
			if($c == null) continue;
			foreach($this->db->getComputerNetwork($c->id) as $n) {
				if(empty($n->mac) || $n->mac == '-' || $n->mac == '?') continue;
				$wolMacAdresses[] = $n->mac;
			}
		}
		if(count($wolMacAdresses) == 0) {
			throw new Exception(LANG['no_mac_addresses_for_wol']);
		}
		wol($wolMacAdresses, $debugOutput);
		return true;
	}
	public function removeComputer($id) {
		$result = $this->db->removeComputer($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		return $result;
	}

}
