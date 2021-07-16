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
		if(!$result) throw new Exception(LANG['not_found']);
		return $result;
	}

	/*** Package Operations ***/
	public function createPackage($name, $version, $description, $author,
		$installProcedure, $installProcedureSuccessReturnCodes, $installProcedurePostAction,
		$uninstallProcedure, $uninstallProcedureSuccessReturnCodes, $downloadForUninstall, $uninstallProcedurePostAction,
		$compatibleOs, $compatibleOsVersion, $tmpFilePath, $fileName=null) {
		if($fileName == null && $tmpFilePath != null) {
			$fileName = basename($tmpFilePath);
		}
		if(empty($name) || empty($installProcedure) || empty($version)) {
			throw new Exception(LANG['please_fill_required_fields']);
		}
		if(!empty($this->db->getPackageByNameVersion($name, $version))) {
			throw new Exception(LANG['package_exists_with_version']);
		}
		// decide what to do with uploaded file
		if($tmpFilePath != null && file_exists($tmpFilePath)) {
			$mimeType = mime_content_type($tmpFilePath);
			if($mimeType != 'application/zip') {
				// create zip with uploaded file if uploaded file is not a zip file
				$newTmpFilePath = '/tmp/ocotmparchive.zip';
				$zip = new ZipArchive();
				if(!$zip->open($newTmpFilePath, ZipArchive::CREATE)) {
					throw new Exception(LANG['cannot_create_zip_file']);
				}
				$zip->addFile($tmpFilePath, '/'.basename($fileName));
				$zip->close();
				$tmpFilePath = $newTmpFilePath;
			}
		}
		// insert into database
		$packageFamilyId = null;
		$existingPackageFamily = $this->db->getPackageFamilyByName($name);
		if($existingPackageFamily === null) $packageFamilyId = $this->db->addPackageFamily($name, '');
		else $packageFamilyId = $existingPackageFamily->id;
		if(!$packageFamilyId) {
			throw new Exception(LANG['database_error']);
		}
		$insertId = $this->db->addPackage(
			$packageFamilyId, $version,
			$author, $description,
			$installProcedure,
			$installProcedureSuccessReturnCodes,
			$installProcedurePostAction,
			$uninstallProcedure,
			$uninstallProcedureSuccessReturnCodes,
			$downloadForUninstall,
			$uninstallProcedurePostAction,
			$compatibleOs, $compatibleOsVersion
		);
		if(!$insertId) {
			throw new Exception(LANG['database_error']);
		}
		// move file to payload dir
		if($tmpFilePath != null && file_exists($tmpFilePath)) {
			$finalFileName = intval($insertId).'.zip';
			$finalFilePath = PACKAGE_PATH.'/'.$finalFileName;
			$result = rename($tmpFilePath, $finalFilePath);
			if(!$result) {
				error_log('Can not move uploaded file to: '.$finalFilePath);
				$this->db->removePackage($insertId);
				throw new Exception(LANG['cannot_move_uploaded_file']);
			}
		}
		return $insertId;
	}
	public function removePackage($id) {
		$package = $this->db->getPackage($id);
		if(empty($package)) throw new Exception(LANG['not_found']);
		$path = $package->getFilePath();
		if(!empty($path)) unlink($path);
		$result = $this->db->removePackage($package->id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		return $result;
	}

	/*** Deployment Operations ***/
	public function deploy($name, $description, $author, $computerIds, $computerGroupIds, $packageIds, $packageGroupIds, $dateStart, $dateEnd, $useWol, $restartTimeout, $autoCreateUninstallJobs, $sequenceMode) {
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
		if($sequenceMode != JobContainer::SEQUENCE_MODE_IGNORE_FAILED
		&& $sequenceMode != JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
			throw new Exception(LANG['invalid_input']);
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
				'install_procedure_post_action' => $p->install_procedure_post_action,
				'compatible_os' => $p->compatible_os,
				'compatible_os_version' => $p->compatible_os_version,
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
					'install_procedure_post_action' => $p->install_procedure_post_action,
					'compatible_os' => $p->compatible_os,
					'compatible_os_version' => $p->compatible_os_version,
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
			$wolSent,
			$sequenceMode
		)) {
			foreach($computer_ids as $computer_id) {
				$sequence = 1;

				foreach($packages as $pid => $package) {

					// check OS compatibility
					$tmpComputer = $this->db->getComputer($computer_id);
					if(!empty($package['compatible_os']) && !empty($tmpComputer->os)
					&& $package['compatible_os'] != $tmpComputer->os) {
						// create failed job
						if($this->db->addJob($jcid, $computer_id,
							$pid, $package['procedure'], $package['success_return_codes'],
							0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
							$package['install_procedure_post_action'], $restartTimeout,
							$sequence, Job::STATUS_OS_INCOMPATIBLE
						)) {
							$sequence ++;
						}
						continue;
					}
					if(!empty($package['compatible_os_version']) && !empty($tmpComputer->os_version)
					&& $package['compatible_os_version'] != $tmpComputer->os_version) {
						// create failed job
						if($this->db->addJob($jcid, $computer_id,
							$pid, $package['procedure'], $package['success_return_codes'],
							0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
							$package['install_procedure_post_action'], $restartTimeout,
							$sequence, Job::STATUS_OS_INCOMPATIBLE
						)) {
							$sequence ++;
						}
						continue;
					}

					// create uninstall jobs
					if(!empty($autoCreateUninstallJobs)) {
						foreach($this->db->getComputerPackage($computer_id) as $cp) {
							// uninstall it, if it is from the same package family
							if($cp->package_family_id === $package['package_family_id']) {
								$cpp = $this->db->getPackage($cp->package_id);
								if($cpp == null) continue;
								$this->db->addJob($jcid, $computer_id,
									$cpp->id, $cpp->uninstall_procedure, $cpp->uninstall_procedure_success_return_codes,
									1/*is_uninstall*/, $cpp->download_for_uninstall,
									$cpp->uninstall_procedure_post_action, $restartTimeout,
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
						$package['install_procedure_post_action'], $restartTimeout,
						$sequence
					)) {
						$sequence ++;
					}
				}
			}
		}

		return $jcid;
	}
	public function uninstall($name, $description, $author, $installationIds, $dateStart, $dateEnd, $useWol, $restartTimeout) {
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

		// wol handling
		$computer_ids = [];
		foreach($installationIds as $id) {
			$ap = $this->db->getComputerAssignedPackage($id);
			if(empty($ap)) continue;
			$computer_ids[] = $ap->computer_id;
		}
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

		// check if there are any computer & packages
		if(count($computer_ids) == 0) {
			throw new Exception(LANG['no_jobs_created']);
		}

		// create jobs
		$jcid = $this->db->addJobContainer(
			$name, $author,
			empty($dateStart) ? date('Y-m-d H:i:s') : $dateStart,
			empty($dateEnd) ? null : $dateEnd,
			$description, $wolSent
		);
		foreach($installationIds as $id) {
			$ap = $this->db->getComputerAssignedPackage($id);
			if(empty($ap)) continue;
			$p = $this->db->getPackage($ap->package_id);
			$this->db->addJob($jcid, $ap->computer_id,
				$ap->package_id, $p->uninstall_procedure, $p->uninstall_procedure_success_return_codes,
				1/*is_uninstall*/, $p->download_for_uninstall,
				$p->uninstall_procedure_post_action, $restartTimeout,
				0/*sequence*/
			);
		}
	}
	public function removeComputerAssignedPackage($id) {
		$result = $this->db->removeComputerAssignedPackage($id);
		if(!$result) throw new Exception(LANG['not_found']);
		return $result;
	}
	public function removeJobContainer($id) {
		$result = $this->db->removeJobContainer($id);
		if(!$result) throw new Exception(LANG['not_found']);
		return $result;
	}
	public function removeJob($id) {
		$result = $this->db->removeJob($id);
		if(!$result) throw new Exception(LANG['not_found']);
		return $result;
	}

}
