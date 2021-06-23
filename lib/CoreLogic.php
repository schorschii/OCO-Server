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

	/*** Deploy Operations ***/
	public function deploy($name, $description, $author, $computerIds, $computerGroupIds, $packageIds, $packageGroupIds, $dateStart, $dateEnd, $useWol, $restartTimeout, $autoCreateUninstallJobs) {
		// check user input
		if(empty($name)) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		if(empty($restartTimeout) || empty($dateStart) || strtotime($dateStart) === false) {
			throw new Exception(LANG['please_fill_required_fields']);
		}
		if(!empty($dateEnd) // check end date if not empty
		&& (strtotime($dateEnd) === false || strtotime($dateStart) >= strtotime($dateEnd))
		) {
			throw new Exception(LANG['end_time_before_start_time']);
		}

		// check if given IDs exists and add them to a consolidated array
		$computer_ids = [];
		$packages = [];
		$computer_group_ids = [];
		$package_group_ids = [];
		if(!empty($computerIds)) foreach($computerIds as $computer_id) {
			if($this->db->getComputer($computer_id) !== null) $computer_ids[$computer_id] = $computer_id;
		}
		if(!empty($computerGroupIds)) foreach($computerGroupIds as $computer_group_id) {
			if($this->db->getComputerGroup($computer_group_id) !== null) $computer_group_ids[] = $computer_group_id;
		}

		if(!empty($packageIds)) foreach($packageIds as $package_id) {
			$p = $this->db->getPackage($package_id);
			if($p !== null) $packages[$p->id] = [
				'package_family_id' => $p->package_family_id,
				'procedure' => $p->install_procedure,
				'success_return_codes' => $p->install_procedure_success_return_codes,
				'install_procedure_restart' => $p->install_procedure_restart,
				'install_procedure_shutdown' => $p->install_procedure_shutdown,
				'download' => $p->getFilePath() ? true : false,
			];
		}
		if(!empty($packageGroupIds)) foreach($packageGroupIds as $package_group_id) {
			if($this->db->getPackageGroup($package_group_id) !== null) $package_group_ids[] = $package_group_id;
		}

		// if multiple groups selected: add all group members
		if(count($computer_group_ids) > 1) foreach($computer_group_ids as $computer_group_id) {
			foreach($this->db->getComputerByGroup($computer_group_id) as $c) {
				$computer_ids[$c->id] = $c->id;
			}
		}
		if(count($package_group_ids) > 1) foreach($package_group_ids as $package_group_id) {
			foreach($this->db->getPackageByGroup($package_group_id) as $p) {
				$packages[$p->id] = [
					'package_family_id' => $p->package_family_id,
					'procedure' => $p->install_procedure,
					'success_return_codes' => $p->install_procedure_success_return_codes,
					'install_procedure_restart' => $p->install_procedure_restart,
					'install_procedure_shutdown' => $p->install_procedure_shutdown,
					'download' => $p->getFilePath() ? true : false,
				];
			}
		}

		// check if there are any computer & packages
		if(count($computer_ids) == 0 || count($packages) == 0) {
			throw new Exception(LANG['no_jobs_created']);
		}

		// wol handling
		$wolSent = -1;
		if($useWol) {
			if(strtotime($dateStart) <= time()) {
				// instant WOL if start time is already in the past
				$wolSent = 1;
				$wolMacAdresses = [];
				foreach($computer_ids as $cid) {
					foreach($this->db->getComputerNetwork($cid) as $cn) {
						$wolMacAdresses[] = $cn->mac;
					}
				}
				wol($wolMacAdresses, false);
			} else {
				$wolSent = 0;
			}
		}

		// create jobs
		if($jcid = $this->db->addJobContainer(
			$name, $author,
			empty($dateStart) ? date('Y-m-d H:i:s') : $dateStart,
			empty($dateEnd) ? null : $dateEnd,
			$description,
			$wolSent
		)) {
			foreach($computer_ids as $computer_id) {
				$sequence = 1;

				foreach($packages as $pid => $package) {

					// create uninstall jobs
					if(!empty($autoCreateUninstallJobs)) {
						foreach($this->db->getComputerPackage($computer_id) as $cp) {
							// uninstall it, if it is from the same package family
							if($cp->package_family_id === $package['package_family_id']) {
								$cpp = $this->db->getPackage($cp->package_id);
								if($cpp == null || empty($cpp->uninstall_procedure)) continue;
								$this->db->addJob($jcid, $computer_id,
									$cpp->id, $cpp->uninstall_procedure, $cpp->uninstall_procedure_success_return_codes,
									1/*is_uninstall*/, $cpp->download_for_uninstall,
									$cpp->uninstall_procedure_restart ? $restartTimeout : -1,
									$cpp->uninstall_procedure_shutdown ? $restartTimeout : -1,
									$sequence
								);
								$sequence ++;
							}
						}
					}

					// create job
					if($this->db->addJob($jcid, $computer_id,
						$pid, $package['procedure'], $package['success_return_codes'],
						0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
						$package['install_procedure_restart'] ? $restartTimeout : -1,
						$package['install_procedure_shutdown'] ? $restartTimeout : -1,
						$sequence
					)) {
						$sequence ++;
					}
				}
			}
		}

		return $jcid;
	}

}
