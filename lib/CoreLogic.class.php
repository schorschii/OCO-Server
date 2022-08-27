<?php

class CoreLogic {

	/*
		 Class CoreLogic
		 Database Abstraction Layer Wrapper

		 Adds additional checks & logic before the database is accessed and sanitizes user input.
		 It's public functions are used by the web frontend and the client API.
	*/

	private $db;
	private $systemUser;

	function __construct($db, $systemUser=null) {
		$this->db = $db;
		$this->systemUser = $systemUser;
	}

	/*** Permission Check Logic ***/
	private function checkPermission(Object $ressource, String $method) {
		// do not check permissions if CoreLogic is used in system context (e.g. cron jobs)
		if($this->systemUser !== null && $this->systemUser instanceof Models\SystemUser)
		return $this->systemUser->checkPermission($ressource, $method, true/*throw*/);
	}

	/*** Computer Operations ***/
	public function getComputers(Object $filterRessource=null) {
		if($filterRessource === null) {
			$computersFiltered = [];
			foreach($this->db->getAllComputer() as $computer) {
				if($this->systemUser->checkPermission($computer, PermissionManager::METHOD_READ, false))
					$computersFiltered[] = $computer;
			}
			return $computersFiltered;
		} elseif($filterRessource instanceof Models\ComputerGroup) {
			$group = $this->db->getComputerGroup($filterRessource->id);
			if(empty($group)) throw new NotFoundException();
			$this->systemUser->checkPermission($group, PermissionManager::METHOD_READ);
			return $this->db->getComputerByGroup($group->id);
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getComputerGroups($parentId=null) {
		$computerGroupsFiltered = [];
		foreach($this->db->getAllComputerGroup($parentId) as $computerGroup) {
			if($this->systemUser->checkPermission($computerGroup, PermissionManager::METHOD_READ, false))
				$computerGroupsFiltered[] = $computerGroup;
		}
		return $computerGroupsFiltered;
	}
	public function getComputer($id) {
		$computer = $this->db->getComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->systemUser->checkPermission($computer, PermissionManager::METHOD_READ);
		return $computer;
	}
	public function getComputerGroup($id) {
		$computerGroup = $this->db->getComputerGroup($id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($computerGroup, PermissionManager::METHOD_READ);
		return $computerGroup;
	}
	public function createComputer($hostname, $notes='') {
		$this->checkPermission(new Models\Computer(), PermissionManager::METHOD_CREATE);

		$finalHostname = trim($hostname);
		if(empty($finalHostname)) {
			throw new InvalidRequestException(LANG['hostname_cannot_be_empty']);
		}
		if($this->db->getComputerByName($finalHostname) !== null) {
			throw new InvalidRequestException(LANG['hostname_already_exists']);
		}
		$insertId = $this->db->addComputer($finalHostname, ''/*Agent Version*/, []/*Networks*/, $notes, ''/*Agent Key*/, ''/*Server Key*/);
		if(!$insertId) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $insertId, 'oco.computer.create', ['hostname'=>$finalHostname, 'notes'=>$notes]);
		return $insertId;
	}
	public function updateComputer($id, $hostname, $notes) {
		$computer = $this->db->getComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_WRITE);

		$finalHostname = trim($hostname);
		if(empty($finalHostname)) {
			throw new InvalidRequestException(LANG['hostname_cannot_be_empty']);
		}
		$checkComputer = $this->db->getComputerByName($finalHostname);
		if($checkComputer !== null && intval($checkComputer->id) !== intval($id)) {
			throw new InvalidRequestException(LANG['hostname_already_exists']);
		}
		$result = $this->db->updateComputer($computer->id, $finalHostname, $notes);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $computer->id, 'oco.computer.update', ['hostname'=>$finalHostname]);
		return $result;
	}
	public function updateComputerForceUpdate($id, $newValue) {
		$computer = $this->db->getComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_WRITE);

		$result = $this->db->updateComputerForceUpdate($computer->id, $newValue);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $computer->id, 'oco.computer.update', ['force_update'=>$newValue]);
		return $result;
	}
	public function wolComputers($ids, $debugOutput=true) {
		$wolMacAdresses = [];
		foreach($ids as $id) {
			$computer = $this->db->getComputer($id);
			if(empty($computer)) throw new NotFoundException();
			$this->checkPermission($computer, PermissionManager::METHOD_WOL);

			foreach($this->db->getComputerNetwork($computer->id) as $n) {
				if(empty($n->mac) || $n->mac == '-' || $n->mac == '?') continue;
				$wolMacAdresses[] = $n->mac;
			}
		}
		if(count($wolMacAdresses) == 0) {
			throw new Exception(LANG['no_mac_addresses_for_wol']);
		}
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, null, 'oco.computer.wol', $wolMacAdresses);
		WakeOnLan::wol($wolMacAdresses, $debugOutput);
		return true;
	}
	public function removeComputer($id, $force=false) {
		$computer = $this->db->getComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$jobs = $this->db->getPendingJobsForComputerDetailPage($id);
			if(count($jobs) > 0) throw new InvalidRequestException(LANG['delete_failed_active_jobs']);
		}
		$result = $this->db->removeComputer($computer->id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $computer->id, 'oco.computer.delete', []);
		return $result;
	}
	public function createComputerGroup($name, $parentGroupId=null) {
		if($parentGroupId == null) {
			$this->systemUser->checkPermission(new Models\ComputerGroup(), PermissionManager::METHOD_CREATE);
		} else {
			$computerGroup = $this->db->getComputerGroup($parentGroupId);
			if(empty($computerGroup)) throw new NotFoundException();
			$this->systemUser->checkPermission($computerGroup, PermissionManager::METHOD_CREATE);
		}

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$insertId = $this->db->addComputerGroup($name, $parentGroupId);
		if(!$insertId) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $insertId, 'oco.computer_group.create', ['name'=>$name, 'parent_computer_group_id'=>$parentGroupId]);
		return $insertId;
	}
	public function renameComputerGroup($id, $newName) {
		$computerGroup = $this->db->getComputerGroup($id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$this->db->renameComputerGroup($computerGroup->id, $newName);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $computerGroup->id, 'oco.computer_group.update', ['name'=>$newName]);
	}
	public function addComputerToGroup($computerId, $groupId) {
		$computer = $this->db->getComputer($computerId);
		if(empty($computer)) throw new NotFoundException();
		$computerGroup = $this->db->getComputerGroup($groupId);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($computer, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);

		if(count($this->db->getComputerByComputerAndGroup($computer->id, $computerGroup->id)) == 0) {
			$this->db->addComputerToGroup($computer->id, $computerGroup->id);
			$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $computerGroup->id, 'oco.computer_group.add_member', ['computer_id'=>$computer->id]);
		}
	}
	public function removeComputerFromGroup($computerId, $groupId) {
		$computer = $this->db->getComputer($computerId);
		if(empty($computer)) throw new NotFoundException();
		$computerGroup = $this->db->getComputerGroup($groupId);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);

		$this->db->removeComputerFromGroup($computer->id, $computerGroup->id);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $computerGroup->id, 'oco.computer_group.remove_member', ['computer_id'=>$computer->id]);
	}
	public function removeComputerGroup($id, $force=false) {
		$computerGroup = $this->db->getComputerGroup($id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($computerGroup, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$subgroups = $this->db->getAllComputerGroup($id);
			if(count($subgroups) > 0) throw new InvalidRequestException(LANG['delete_failed_subgroups']);
		}
		$result = $this->db->removeComputerGroup($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $computerGroup->id, 'oco.computer_group.delete', []);
		return $result;
	}

	/*** Package Operations ***/
	public function getPackages(Object $filterRessource=null) {
		if($filterRessource === null) {
			$packagesFiltered = [];
			foreach($this->db->getAllPackage() as $package) {
				if($this->systemUser->checkPermission($package, PermissionManager::METHOD_READ, false))
					$packagesFiltered[] = $package;
			}
			return $packagesFiltered;
		} elseif($filterRessource instanceof Models\PackageFamily) {
			$family = $this->db->getPackageFamily($filterRessource->id);
			if(empty($family)) throw new NotFoundException();
			$this->systemUser->checkPermission($family, PermissionManager::METHOD_READ);
			return $this->db->getPackageByFamily($family->id);
		} elseif($filterRessource instanceof Models\PackageGroup) {
			$group = $this->db->getPackageGroup($filterRessource->id);
			if(empty($group)) throw new NotFoundException();
			$this->systemUser->checkPermission($group, PermissionManager::METHOD_READ);
			return $this->db->getPackageByGroup($group->id);
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getPackageGroups($parentId=null) {
		$packageGroupsFiltered = [];
		foreach($this->db->getAllPackageGroup($parentId) as $packageGroup) {
			if($this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_READ, false))
				$packageGroupsFiltered[] = $packageGroup;
		}
		return $packageGroupsFiltered;
	}
	public function getPackageFamilies(Object $filterRessource=null, $binaryAsBase64=false) {
		if($filterRessource === null) {
			$packageFamiliesFiltered = [];
			foreach($this->db->getAllPackageFamily($binaryAsBase64) as $packageFamily) {
				if($this->systemUser->checkPermission($packageFamily, PermissionManager::METHOD_READ, false))
					$packageFamiliesFiltered[] = $packageFamily;
			}
			return $packageFamiliesFiltered;
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getPackage($id) {
		$package = $this->db->getPackage($id);
		if(empty($package)) throw new NotFoundException();
		$this->systemUser->checkPermission($package, PermissionManager::METHOD_READ);
		return $package;
	}
	public function getPackageGroup($id) {
		$packageGroup = $this->db->getPackageGroup($id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_READ);
		return $packageGroup;
	}
	public function getPackageFamily($id) {
		$packageFamily = $this->db->getPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageFamily, PermissionManager::METHOD_READ);
		return $packageFamily;
	}
	public function createPackage($name, $version, $description, $author,
		$installProcedure, $installProcedureSuccessReturnCodes, $installProcedurePostAction,
		$uninstallProcedure, $uninstallProcedureSuccessReturnCodes, $downloadForUninstall, $uninstallProcedurePostAction,
		$compatibleOs, $compatibleOsVersion, $tmpFilePath, $fileName=null) {

		$this->systemUser->checkPermission(new Models\Package(), PermissionManager::METHOD_CREATE);

		if($fileName == null && $tmpFilePath != null) {
			$fileName = basename($tmpFilePath);
		}
		if(empty($name) || empty($installProcedure) || empty($version)) {
			throw new InvalidRequestException(LANG['please_fill_required_fields']);
		}
		if(!empty($this->db->getPackageByNameVersion($name, $version))) {
			throw new InvalidRequestException(LANG['package_exists_with_version']);
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
				$zip->addFile($tmpFilePath, basename($fileName));
				$zip->close();
				$tmpFilePath = $newTmpFilePath;
			}
		}
		// insert into database
		$packageFamilyId = null;
		$existingPackageFamily = $this->db->getPackageFamilyByName($name);
		if($existingPackageFamily === null) {
			$this->systemUser->checkPermission(new Models\PackageFamily(), PermissionManager::METHOD_CREATE);

			$packageFamilyId = $this->db->addPackageFamily($name, '');
		} else {
			$this->systemUser->checkPermission($existingPackageFamily, PermissionManager::METHOD_CREATE);

			$packageFamilyId = $existingPackageFamily->id;
		}
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
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $insertId, 'oco.package.create', ['package_family_id'=>$packageFamilyId, 'version'=>$version]);
		return $insertId;
	}
	public function addPackageToGroup($packageId, $groupId) {
		$package = $this->db->getPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$packageGroup = $this->db->getPackageGroup($groupId);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($package, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		if(count($this->db->getPackageByPackageAndGroup($package->id, $packageGroup->id)) == 0) {
			$this->db->addPackageToGroup($package->id, $packageGroup->id);
			$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageGroup->id, 'oco.package_group.add_member', ['package_id'=>$package->id]);
		}
	}
	public function removePackageFromGroup($packageId, $groupId) {
		$package = $this->db->getPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$packageGroup = $this->db->getPackageGroup($groupId);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		$this->db->removePackageFromGroup($package->id, $packageGroup->id);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageGroup->id, 'oco.package_group.remove_member', ['package_id'=>$package->id]);
	}
	public function removePackage($id, $force=false) {
		$package = $this->db->getPackage($id);
		if(empty($package)) throw new NotFoundException();
		$this->systemUser->checkPermission($package, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$jobs = $this->db->getPendingJobsForPackageDetailPage($id);
			if(count($jobs) > 0) throw new InvalidRequestException(LANG['delete_failed_active_jobs']);

			$dependentPackages = $this->db->getDependentForPackages($id);
			if(count($dependentPackages) > 0) throw new InvalidRequestException(LANG['delete_failed_dependent_packages']);
		}

		$path = $package->getFilePath();
		if(!empty($path)) unlink($path);

		$result = $this->db->removePackage($package->id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $package->id, 'oco.package.delete', []);
		return $result;
	}
	public function removePackageFamily($id) {
		$packageFamily = $this->db->getPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageFamily, PermissionManager::METHOD_DELETE);

		$packages = $this->db->getPackageByFamily($id);
		if(count($packages) > 0) throw new InvalidRequestException(LANG['delete_failed_package_family_contains_packages']);

		$result = $this->db->removePackageFamily($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageFamily->id, 'oco.package_family.delete', []);
		return $result;
	}
	public function createPackageGroup($name, $parentGroupId=null) {
		if($parentGroupId == null) {
			$this->systemUser->checkPermission(new Models\PackageGroup(), PermissionManager::METHOD_CREATE);
		} else {
			$packageGroup = $this->db->getPackageGroup($parentGroupId);
			if(empty($packageGroup)) throw new NotFoundException();
			$this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_CREATE);
		}

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$insertId = $this->db->addPackageGroup($name, $parentGroupId);
		if(!$insertId) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $insertId, 'oco.package_group.create', ['name'=>$name, 'parent_packge_group_id'=>$parentGroupId]);
		return $insertId;
	}
	public function removePackageGroup($id, $force=false) {
		$packageGroup = $this->db->getPackageGroup($id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$subgroups = $this->db->getAllPackageGroup($id);
			if(count($subgroups) > 0) throw new InvalidRequestException(LANG['delete_failed_subgroups']);
		}

		$result = $this->db->removePackageGroup($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageGroup->id, 'oco.package_group.delete', []);
		return $result;
	}
	public function renamePackageFamily($id, $newName) {
		$packageFamily = $this->db->getPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageFamily, PermissionManager::METHOD_WRITE);

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$this->db->updatePackageFamily($packageFamily->id, $newName, $packageFamily->notes, $packageFamily->icon);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageFamily->id, 'oco.package_family.update', ['name'=>$newName]);
	}
	public function updatePackageFamilyNotes($id, $notes) {
		$packageFamily = $this->db->getPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageFamily, PermissionManager::METHOD_WRITE);

		$this->db->updatePackageFamily($packageFamily->id, $packageFamily->name, $notes, $packageFamily->icon);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageFamily->id, 'oco.package_family.update', ['notes'=>$notes]);
	}
	public function updatePackageFamilyIcon($id, $icon) {
		$packageFamily = $this->db->getPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageFamily, PermissionManager::METHOD_WRITE);

		$this->db->updatePackageFamily($packageFamily->id, $packageFamily->name, $packageFamily->notes, $icon);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageFamily->id, 'oco.package_family.update', ['icon'=>base64_encode($icon)]);
	}
	public function addPackageDependency($packageId, $dependentPackageId) {
		$package = $this->db->getPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$dependentPackage = $this->db->getPackage($dependentPackageId);
		if(empty($dependentPackage)) throw new NotFoundException();
		$this->systemUser->checkPermission($package, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission($dependentPackage, PermissionManager::METHOD_WRITE);

		$this->db->addPackageDependency($package->id, $dependentPackage->id);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $package->id, 'oco.package.add_dependency', ['dependent_package_id'=>$dependentPackage->id]);
	}
	public function removePackageDependency($packageId, $dependentPackageId) {
		$package = $this->db->getPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$dependentPackage = $this->db->getPackage($dependentPackageId);
		if(empty($dependentPackage)) throw new NotFoundException();
		$this->systemUser->checkPermission($package, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission($dependentPackage, PermissionManager::METHOD_WRITE);

		$this->db->removePackageDependency($package->id, $dependentPackage->id);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $package->id, 'oco.package.remove_dependency', ['dependent_package_id'=>$dependentPackage->id]);
	}
	public function updatePackage($id, $package_family_id, $version, $compatibleOs, $compatibleOsVersion, $notes, $installProcedure, $installProcedureSuccessReturnCodes, $installProcedurePostAction, $uninstallProcedure, $uninstallProcedureSuccessReturnCodes, $uninstallProcedurePostAction, $downloadForUninstall) {
		$package = $this->db->getPackage($id);
		if(empty($package)) throw new NotFoundException();
		$this->systemUser->checkPermission($package, PermissionManager::METHOD_WRITE);

		$package_family = $this->db->getPackageFamily($package_family_id);
		if(empty($package_family)) throw new NotFoundException();
		$this->systemUser->checkPermission($package_family, PermissionManager::METHOD_WRITE);

		if(empty(trim($version))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		if(!is_numeric($installProcedurePostAction)
		|| !in_array($installProcedurePostAction, [Models\Package::POST_ACTION_NONE, Models\Package::POST_ACTION_RESTART, Models\Package::POST_ACTION_SHUTDOWN, Models\Package::POST_ACTION_EXIT])) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}
		if(!is_numeric($uninstallProcedurePostAction)
		|| !in_array($uninstallProcedurePostAction, [Models\Package::POST_ACTION_NONE, Models\Package::POST_ACTION_RESTART, Models\Package::POST_ACTION_SHUTDOWN])) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}
		if(intval($downloadForUninstall) !== 0 && intval($downloadForUninstall) !== 1) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}

		$this->db->updatePackage($package->id,
			$package_family_id,
			$package->author,
			$version,
			$compatibleOs,
			$compatibleOsVersion,
			$notes,
			$installProcedure,
			$installProcedureSuccessReturnCodes,
			intval($installProcedurePostAction),
			$uninstallProcedure,
			$uninstallProcedureSuccessReturnCodes,
			intval($uninstallProcedurePostAction),
			intval($downloadForUninstall),
		);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $package->id, 'oco.package.update', [
			'version'=>$version,
			'compatible_os'=>$compatibleOs,
			'compatible_os_version'=>$compatibleOsVersion,
			'notes'=>$notes,
			'install_procedure'=>$installProcedure,
			'install_procedure_success_return_codes'=>$installProcedureSuccessReturnCodes,
			'install_procedure_post_action'=>$installProcedurePostAction,
			'uninstall_procedure'=>$uninstallProcedure,
			'uninstall_procedure_success_return_codes'=>$uninstallProcedureSuccessReturnCodes,
			'uninstall_procedure_post_action'=>$uninstallProcedurePostAction,
			'download_for_uninstall'=>$downloadForUninstall,
		]);
	}
	public function reorderPackageInGroup($groupId, $oldPos, $newPos) {
		$packageGroup = $this->db->getPackageGroup($groupId);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		$this->db->reorderPackageInGroup($packageGroup->id, $oldPos, $newPos);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageGroup->id, 'oco.package_group.reorder', ['old_pos'=>$oldPos, 'new_pos'=>$newPos]);
	}
	public function renamePackageGroup($id, $newName) {
		$packageGroup = $this->db->getPackageGroup($id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($packageGroup, PermissionManager::METHOD_DELETE);

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$this->db->renamePackageGroup($packageGroup->id, $newName);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $packageGroup->id, 'oco.package_group.update', ['name'=>$newName]);
	}

	/*** Deployment / Job Container Operations ***/
	public function getJobContainers(Object $filterRessource=null) {
		if($filterRessource === null) {
			$jobContainersFiltered = [];
			foreach($this->db->getAllJobContainer() as $jobContainer) {
				if($this->systemUser->checkPermission($jobContainer, PermissionManager::METHOD_READ, false))
					$jobContainersFiltered[] = $jobContainer;
			}
			return $jobContainersFiltered;
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getJobContainer($id) {
		$jobContainer = $this->db->getJobContainer($id);
		if(empty($jobContainer)) throw new NotFoundException();
		$this->systemUser->checkPermission($jobContainer, PermissionManager::METHOD_READ);
		return $jobContainer;
	}
	public function deploy($name, $description, $author, $computerIds, $computerGroupIds, $computerReportIds, $packageIds, $packageGroupIds, $packageReportIds, $dateStart, $dateEnd, $useWol, $shutdownWakedAfterCompletion, $restartTimeout, $autoCreateUninstallJobs, $forceInstallSameVersion, $sequenceMode, $priority, $constraintIpRanges=[]) {
		$this->systemUser->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE);

		// check user input
		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		if(empty($restartTimeout) || empty($dateStart) || strtotime($dateStart) === false) {
			throw new InvalidRequestException(LANG['please_fill_required_fields']);
		}
		if(!empty($dateEnd) // check end date if not empty
		&& (strtotime($dateEnd) === false || strtotime($dateStart) >= strtotime($dateEnd))
		) {
			throw new InvalidRequestException(LANG['end_time_before_start_time']);
		}
		if($sequenceMode != Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED
		&& $sequenceMode != Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}
		if($priority < -100 || $priority > 100) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}

		// check if given IDs exists and add them to a consolidated array
		$computer_ids = [];
		$packages = [];
		$computer_group_ids = [];
		$package_group_ids = [];
		$computer_report_ids = [];
		$package_report_ids = [];
		if(!empty($computerIds)) foreach($computerIds as $computer_id) {
			$tmpComputer = $this->db->getComputer($computer_id);
			if($tmpComputer == null) continue;
			if(!$this->systemUser->checkPermission($tmpComputer, PermissionManager::METHOD_DEPLOY, false)) continue;
			$computer_ids[$computer_id] = $computer_id;
		}
		if(!empty($computerGroupIds)) foreach($computerGroupIds as $computer_group_id) {
			$tmpComputerGroup = $this->db->getComputerGroup($computer_group_id);
			if($tmpComputerGroup == null) continue;
			$computer_group_ids[] = $computer_group_id;
		}
		if(!empty($computerReportIds)) foreach($computerReportIds as $computer_report_id) {
			$tmpComputerReport = $this->db->getReport($computer_report_id);
			if($tmpComputerReport == null) continue;
			$computer_report_ids[] = $computer_report_id;
		}

		if(!empty($packageIds)) foreach($packageIds as $package_id) {
			$packages = $packages + $this->compileDeployPackageArray($package_id);
		}
		if(!empty($packageGroupIds)) foreach($packageGroupIds as $package_group_id) {
			$tmpPackageGroup = $this->db->getPackageGroup($package_group_id);
			if($tmpPackageGroup == null) continue;
			$package_group_ids[] = $package_group_id;
		}
		if(!empty($packageReportIds)) foreach($packageReportIds as $package_report_id) {
			$tmpPackageReport = $this->db->getReport($package_report_id);
			if($tmpPackageReport == null) continue;
			$package_report_ids[] = $package_report_id;
		}

		// add all group members
		foreach($computer_group_ids as $computer_group_id) {
			foreach($this->db->getComputerByGroup($computer_group_id) as $c) {
				if(!$this->systemUser->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
				$computer_ids[$c->id] = $c->id;
			}
		}
		foreach($package_group_ids as $package_group_id) {
			foreach($this->db->getPackageByGroup($package_group_id) as $p) {
				$packages = $packages + $this->compileDeployPackageArray($p->id);
			}
		}

		// add all report results
		foreach($computer_report_ids as $computer_report_id) {
			//try { // ignore report with syntax errors
				foreach($this->executeReport($computer_report_id) as $row) {
					$c = $this->db->getComputer($row['computer_id']);
					if(empty($c) || !$this->systemUser->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
					$computer_ids[$c->id] = $c->id;
				}
			//} catch(Exception $ignored) {
			//	// in case of execption we have a pending transaction
			//	$this->db->getDbHandle()->rollBack();
			//}
		}
		foreach($package_report_ids as $package_report_id) {
			//try { // ignore report with syntax errors
				foreach($this->executeReport($package_report_id) as $row) {
					$p = $this->db->getPackage($row['package_id']);
					if(empty($p)) continue;
					$packages = $packages + $this->compileDeployPackageArray($p->id);
				}
			//} catch(Exception $ignored) {
			//	// in case of execption we have a pending transaction
			//	$this->db->getDbHandle()->rollBack();
			//}
		}

		// check if there are any computer & packages
		if(count($computer_ids) == 0 || count($packages) == 0) {
			throw new InvalidRequestException(LANG['no_jobs_created']);
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
				WakeOnLan::wol($wolMacAdresses, false);
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
			$wolSent, $shutdownWakedAfterCompletion,
			$sequenceMode, $priority, $this->compileIpRanges($constraintIpRanges)
		)) {
			foreach($computer_ids as $computer_id) {

				$tmpComputer = $this->db->getComputer($computer_id);
				$createdUninstallJobs = [];
				$sequence = 1;

				foreach($packages as $pid => $package) {

					$targetJobState = Models\Job::STATUS_WAITING_FOR_CLIENT;

					// check OS compatibility
					if(!empty($package['compatible_os']) && !empty($tmpComputer->os)
					&& $package['compatible_os'] != $tmpComputer->os) {
						// create failed job
						if($this->db->addJob($jcid, $computer_id,
							$pid, $package['procedure'], $package['success_return_codes'],
							0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
							$package['install_procedure_post_action'], $restartTimeout,
							$sequence, Models\Job::STATUS_OS_INCOMPATIBLE
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
							$sequence, Models\Job::STATUS_OS_INCOMPATIBLE
						)) {
							$sequence ++;
						}
						continue;
					}

					foreach($this->db->getComputerPackage($computer_id) as $cp) {
						// ignore if it is an automatically added dependency package and version is the same as already installed
						if($package['is_dependency'] && strval($pid) === $cp->package_id) continue 2;

						// if the same version is already installed, add a informative job with status STATUS_ALREADY_INSTALLED
						if($cp->package_id === strval($pid) && empty($forceInstallSameVersion)) {
							$targetJobState = Models\Job::STATUS_ALREADY_INSTALLED;
							break;
						}

						// if requested, automagically create uninstall jobs
						if(!empty($autoCreateUninstallJobs)) {
							// uninstall it, if it is from the same package family ...
							if($cp->package_family_id === $package['package_family_id']) {
								$cpp = $this->db->getPackage($cp->package_id, null);
								if($cpp == null) continue;
								// ... but not, if it is another package family version and autoCreateUninstallJobs flag is set
								if($cp->package_id !== strval($pid) && empty($autoCreateUninstallJobs)) continue;
								// ... and not, if this uninstall job was already created
								if(in_array($cp->id, $createdUninstallJobs)) continue;
								$createdUninstallJobs[] = $cp->id;
								$this->db->addJob($jcid, $computer_id,
									$cpp->id, $cpp->uninstall_procedure, $cpp->uninstall_procedure_success_return_codes,
									1/*is_uninstall*/, $cpp->download_for_uninstall,
									$cpp->uninstall_procedure_post_action, $restartTimeout,
									$sequence, Models\Job::STATUS_WAITING_FOR_CLIENT
								);
								$sequence ++;
							}
						}
					}

					// create installation job
					if($this->db->addJob($jcid, $computer_id,
						$pid, $package['procedure'], $package['success_return_codes'],
						0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
						$package['install_procedure_post_action'], $restartTimeout,
						$sequence, $targetJobState
					)) {
						$sequence ++;
					}

				}

			}

			// if instant WOL: check if computers are currently online (to know if we should shut them down after all jobs are done)
			if(strtotime($dateStart) <= time() && $useWol && $shutdownWakedAfterCompletion) {
				$this->db->setComputerOnlineStateForWolShutdown($jcid);
			}

		}

		$jobs = $this->db->getAllJobByContainer($jcid);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $jcid, 'oco.job_container.create', ['name'=>$name, 'jobs'=>$jobs]);
		return $jcid;
	}
	private function compileIpRanges(Array $constraintIpRanges) {
		$validatedIpRanges = [];
		if(!empty($constraintIpRanges) && is_array($constraintIpRanges)) {
			foreach($constraintIpRanges as $range) {
				if(!is_string($range)) continue;
				$trimmedRange = trim($range);
				if(empty($trimmedRange)) continue;

				// for IP syntax check only (throws error if invalid)
				if(startsWith($trimmedRange, '!')) {
					isIpInRange('0.0.0.0', ltrim($trimmedRange, '!'));
				} else {
					isIpInRange('0.0.0.0', $trimmedRange);
				}

				// add to IP range constraint array
				$validatedIpRanges[] = trim($range);
			}
		}
		return empty($validatedIpRanges) ? null : implode(',', $validatedIpRanges);
	}
	private function compileDeployPackageArray($packageId, $isDependency=false) {
		$packages = [];

		// check if id exists
		$p = $this->db->getPackage($packageId, null);
		if($p == null) return [];
		if(!$this->systemUser->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) return [];

		// recursive dependency resolver
		foreach($this->db->getDependentPackages($p->id) as $p2) {
			$packages = $packages + $this->compileDeployPackageArray($p2->id, true);
		}

		// add package after dependencies
		// note: PHP automatically treats string value $p->id as integer when using numeric strings as array key
		$packages[$p->id] = [
			'package_family_id' => $p->package_family_id,
			'procedure' => $p->install_procedure,
			'success_return_codes' => $p->install_procedure_success_return_codes,
			'install_procedure_post_action' => $p->install_procedure_post_action,
			'compatible_os' => $p->compatible_os,
			'compatible_os_version' => $p->compatible_os_version,
			'download' => $p->getFilePath() ? true : false,
			'is_dependency' => $isDependency,
		];

		return $packages;
	}
	public function uninstall($name, $description, $author, $installationIds, $dateStart, $dateEnd, $useWol, $shutdownWakedAfterCompletion, $restartTimeout, $sequenceMode=0, $priority=0, $constraintIpRanges=[]) {
		$this->systemUser->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE);

		// check user input
		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		if(empty($restartTimeout) || empty($dateStart) || strtotime($dateStart) === false) {
			throw new InvalidRequestException(LANG['please_fill_required_fields']);
		}
		if(!empty($dateEnd) // check end date if not empty
		&& (strtotime($dateEnd) === false || strtotime($dateStart) >= strtotime($dateEnd))
		) {
			throw new InvalidRequestException(LANG['end_time_before_start_time']);
		}
		if($sequenceMode != Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED
		&& $sequenceMode != Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}
		if($priority < -100 || $priority > 100) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}

		// wol handling
		$computer_ids = [];
		foreach($installationIds as $id) {
			$ap = $this->db->getComputerAssignedPackage($id);
			if(empty($ap)) continue;
			$tmpComputer = $this->db->getComputer($ap->computer_id);
			if(empty($tmpComputer)) continue;
			if(!$this->systemUser->checkPermission($tmpComputer, PermissionManager::METHOD_DEPLOY, false)) continue;

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
				WakeOnLan::wol($wolMacAdresses, false);
			} else {
				$wolSent = 0;
			}
		}

		// check if there are any computer & packages
		if(count($computer_ids) == 0) {
			throw new InvalidRequestException(LANG['no_jobs_created']);
		}

		// create jobs
		$jobs = [];
		$jcid = $this->db->addJobContainer(
			$name, $author,
			empty($dateStart) ? date('Y-m-d H:i:s') : $dateStart,
			empty($dateEnd) ? null : $dateEnd,
			$description, $wolSent, $shutdownWakedAfterCompletion, $sequenceMode, $priority, $this->compileIpRanges($constraintIpRanges)
		);
		foreach($installationIds as $id) {
			$ap = $this->db->getComputerAssignedPackage($id); if(empty($ap)) continue;
			$p = $this->db->getPackage($ap->package_id); if(empty($p)) continue;
			$c = $this->db->getComputer($ap->computer_id); if(empty($c)) continue;

			if(!$this->systemUser->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
			if(!$this->systemUser->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;

			$jid = $this->db->addJob($jcid, $ap->computer_id,
				$ap->package_id, $p->uninstall_procedure, $p->uninstall_procedure_success_return_codes,
				1/*is_uninstall*/, $p->download_for_uninstall,
				$p->uninstall_procedure_post_action, $restartTimeout,
				0/*sequence*/
			);
			$jobs[] = $this->db->getJob($jid);
		}
		// if instant WOL: check if computers are currently online (to know if we should shut them down after all jobs are done)
		if(strtotime($dateStart) <= time() && $useWol && $shutdownWakedAfterCompletion) {
			$this->db->setComputerOnlineStateForWolShutdown($jcid);
		}

		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $jcid, 'oco.job_container.create', ['name'=>$name, 'jobs'=>$jobs]);
	}
	public function removeComputerAssignedPackage($id) {
		$computerPackageAssignment = $this->db->getComputerAssignedPackage($id);
		if(!$computerPackageAssignment) throw new NotFoundException();
		$computer = $this->db->getComputer($computerPackageAssignment->computer_id);
		if(!$computer) throw new NotFoundException();
		$package = $this->db->getPackage($computerPackageAssignment->package_id);
		if(!$package) throw new NotFoundException();

		$this->systemUser->checkPermission($computer, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission($package, PermissionManager::METHOD_WRITE);

		$result = $this->db->removeComputerAssignedPackage($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $package->id, 'oco.package.remove_assignment', ['computer_id'=>$computer->id]);
		return $result;
	}
	public function renewFailedJobsInContainer($name, $description, $author, $renewContainerId, $dateStart, $dateEnd, $useWol, $shutdownWakedAfterCompletion, $sequenceMode=0, $priority=0) {
		$jc = $this->db->getJobContainer($renewContainerId);
		if(!$jc) throw new NotFoundException();
		$this->systemUser->checkPermission($jc, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE);

		// check user input
		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		if(empty($dateStart) || strtotime($dateStart) === false) {
			throw new InvalidRequestException(LANG['please_fill_required_fields']);
		}
		if(!empty($dateEnd) // check end date if not empty
		&& (strtotime($dateEnd) === false || strtotime($dateStart) >= strtotime($dateEnd))
		) {
			throw new InvalidRequestException(LANG['end_time_before_start_time']);
		}
		if($sequenceMode != Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED
		&& $sequenceMode != Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}
		if($priority < -100 || $priority > 100) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}

		// get old job container
		$container = $this->db->getJobContainer($renewContainerId);
		if($container === null) {
			throw new NotFoundException();
		}

		// wol handling
		$computer_ids = [];
		foreach($this->db->getAllJobByContainer($container->id) as $job) {
			if($job->state == Models\Job::STATUS_FAILED
			|| $job->state == Models\Job::STATUS_EXPIRED
			|| $job->state == Models\Job::STATUS_OS_INCOMPATIBLE
			|| $job->state == Models\Job::STATUS_PACKAGE_CONFLICT) {
				$computer_ids[] = $job->computer_id;
			}
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
				WakeOnLan::wol($wolMacAdresses, false);
			} else {
				$wolSent = 0;
			}
		}

		// create renew jobs
		$jobs = [];
		if($jcid = $this->db->addJobContainer(
			$name, $author,
			$dateStart, empty($dateEnd) ? null : $dateEnd,
			$description, $wolSent, $shutdownWakedAfterCompletion,
			$sequenceMode, $priority, $container->agent_ip_ranges
		)) {
			foreach($this->db->getAllJobByContainer($container->id) as $job) {
				if($job->state == Models\Job::STATUS_FAILED
				|| $job->state == Models\Job::STATUS_EXPIRED
				|| $job->state == Models\Job::STATUS_OS_INCOMPATIBLE
				|| $job->state == Models\Job::STATUS_PACKAGE_CONFLICT) {

					// use the current package procedure, return codes, post action
					$package = $this->db->getPackage($job->package_id);
					$newJob = new Models\Job();
					$newJob->job_container_id = $jcid;
					$newJob->computer_id = $job->computer_id;
					$newJob->package_id = $job->package_id;
					$newJob->package_procedure = empty($job->is_uninstall) ? $package->install_procedure : $package->uninstall_procedure;
					$newJob->success_return_codes = empty($job->is_uninstall) ? $package->install_procedure_success_return_codes : $package->uninstall_procedure_success_return_codes;
					$newJob->is_uninstall = $job->is_uninstall;
					$newJob->download = $package->getFilePath() ? 1 : 0;
					$newJob->post_action = empty($job->is_uninstall) ? $package->install_procedure_post_action : $package->uninstall_procedure_post_action;
					$newJob->post_action_timeout = $job->post_action_timeout;
					$newJob->sequence = $job->sequence;

					if($this->db->addJob($newJob->job_container_id,
						$newJob->computer_id, $newJob->package_id,
						$newJob->package_procedure, $newJob->success_return_codes,
						$newJob->is_uninstall, $newJob->download,
						$newJob->post_action, $newJob->post_action_timeout,
						$newJob->sequence
					)) {
						$jobs[] = $newJob;
						$this->db->removeJob($job->id);
					}
				}
			}

			// check if there are any computer & packages
			if(count($jobs) == 0) {
				$this->db->removeJobContainer($jcid);
				throw new InvalidRequestException(LANG['no_jobs_created']);
			}

			// if instant WOL: check if computers are currently online (to know if we should shut them down after all jobs are done)
			if(strtotime($dateStart) <= time() && $useWol && $shutdownWakedAfterCompletion) {
				$this->db->setComputerOnlineStateForWolShutdown($jcid);
			}

			$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $jcid, 'oco.job_container.create', ['name'>=$name, 'jobs'=>$jobs]);
		}
	}
	public function updateJobContainer($id, $name, $enabled, $start_time, $end_time, $notes, $sequence_mode, $priority, $agent_ip_ranges) {
		$jc = $this->db->getJobContainer($id);
		if(empty($jc)) throw new NotFoundException();
		$this->systemUser->checkPermission($jc, PermissionManager::METHOD_WRITE);

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		if(!in_array($enabled, ['0', '1'])) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}
		if(DateTime::createFromFormat('Y-m-d H:i:s', $start_time) === false) {
			throw new InvalidRequestException(LANG['date_parse_error']);
		}
		if(!empty($end_time) && DateTime::createFromFormat('Y-m-d H:i:s', $end_time) === false) {
			throw new InvalidRequestException(LANG['date_parse_error']);
		}
		if(empty($end_time)) {
			$end_time = null;
		} else {
			if(strtotime($jc->start_time) > strtotime($end_time)) {
				throw new InvalidRequestException(LANG['end_time_before_start_time']);
			}
		}
		if(!is_numeric($sequence_mode)
		|| !in_array($sequence_mode, [Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED, Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED])) {
			throw new InvalidRequestException(LANG['invalid_input']);
		}
		if(!is_numeric($priority) || intval($priority) < -100 || intval($priority) > 100) {
			error_log($priority);
			throw new InvalidRequestException(LANG['invalid_input']);
		}

		$this->db->updateJobContainer($jc->id,
			$name,
			$enabled,
			$start_time,
			$end_time,
			$notes,
			$jc->wol_sent,
			$jc->shutdown_waked_after_completion,
			$sequence_mode,
			$priority,
			$agent_ip_ranges
		);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $jc->id, 'oco.job_container.update', [
			'name'=>$name,
			'enabled'=>$enabled,
			'start_time'=>$start_time,
			'end_time'=>$end_time,
			'notes'=>$notes,
			'sequence_mode'=>$sequence_mode,
			'priority'=>$priority,
			'agent_ip_ranges'=>$agent_ip_ranges,
		]);
	}
	public function moveJobToContainer($jobId, $containerId) {
		$job = $this->db->getJob($jobId);
		if(empty($job)) throw new NotFoundException();
		$oldContainer = $this->db->getJobContainer($job->job_container_id);
		if(empty($oldContainer)) throw new NotFoundException();
		$newContainer = $this->db->getJobContainer($containerId);
		if(empty($newContainer)) throw new NotFoundException();
		$this->systemUser->checkPermission($oldContainer, PermissionManager::METHOD_DELETE);
		$this->systemUser->checkPermission($oldContainer, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission($newContainer, PermissionManager::METHOD_WRITE);

		$this->db->moveJobToContainer($job->id, $newContainer->id);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $newContainer->id, 'oco.job_container.move_jobs', [
			'old_container_id'=>$oldContainer->id,
			'new_container_id'=>$newContainer->id,
			'job_id'=>$job->id,
		]);
	}
	public function removeJobContainer($id) {
		$jc = $this->db->getJobContainer($id);
		if(empty($jc)) throw new NotFoundException();
		$this->systemUser->checkPermission($jc, PermissionManager::METHOD_DELETE);

		$result = $this->db->removeJobContainer($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $jc->id, 'oco.job_container.delete', []);
		return $result;
	}
	public function removeJob($id) {
		$job = $this->db->getJob($id);
		if(empty($job)) throw new NotFoundException();
		$jc = $this->db->getJobContainer($job->job_container_id);
		if(empty($jc)) throw new NotFoundException();
		$this->systemUser->checkPermission($jc, PermissionManager::METHOD_DELETE);

		$result = $this->db->removeJob($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $job->id, 'oco.job.delete', ['job_container_id'=>$jc->id]);
		return $result;
	}

	/*** Report Operations ***/
	public function getReports(Object $filterRessource=null) {
		if($filterRessource === null) {
			$reportsFiltered = [];
			foreach($this->db->getAllReport() as $report) {
				if($this->systemUser->checkPermission($report, PermissionManager::METHOD_READ, false))
					$reportsFiltered[] = $report;
			}
			return $reportsFiltered;
		} elseif($filterRessource instanceof Models\ReportGroup) {
			$group = $this->db->getReportGroup($filterRessource->id);
			if(empty($group)) throw new NotFoundException();
			$this->systemUser->checkPermission($group, PermissionManager::METHOD_READ);
			return $this->db->getAllReportByGroup($group->id);
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getReportGroups($parentId=null) {
		$reportGroupsFiltered = [];
		foreach($this->db->getAllReportGroup($parentId) as $reportGroup) {
			if($this->systemUser->checkPermission($reportGroup, PermissionManager::METHOD_READ, false))
				$reportGroupsFiltered[] = $reportGroup;
		}
		return $reportGroupsFiltered;
	}
	public function getReport($id) {
		$report = $this->db->getReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->systemUser->checkPermission($report, PermissionManager::METHOD_READ);
		return $report;
	}
	public function executeReport($id) {
		$report = $this->db->getReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->systemUser->checkPermission($report, PermissionManager::METHOD_READ);
		return $this->db->executeReport($report->id);
	}
	public function getReportGroup($id) {
		$reportGroup = $this->db->getReportGroup($id);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($reportGroup, PermissionManager::METHOD_READ);
		return $reportGroup;
	}
	public function createReport($name, $notes, $query, $groupId=0) {
		$this->systemUser->checkPermission(new Models\Report(), PermissionManager::METHOD_CREATE);
		if(!empty($groupId)) {
			$reportGroup = $this->db->getReportGroup($groupId);
			if(empty($reportGroup)) throw new NotFoundException();
			$this->systemUser->checkPermission($reportGroup, PermissionManager::METHOD_WRITE);
		}

		if(empty(trim($name)) || empty(trim($query))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$insertId = $this->db->addReport($groupId, $name, $notes, $query);
		if(!$insertId) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $insertId, 'oco.report.create', ['name'=>$name, 'notes'=>$notes, 'query'=>$query, 'report_group_id'=>$groupId]);
		return $insertId;
	}
	public function updateReport($id, $name, $notes, $query) {
		$report = $this->db->getReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->systemUser->checkPermission($report, PermissionManager::METHOD_WRITE);

		if(empty(trim($name)) || empty(trim($query))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$this->db->updateReport($report->id, $report->report_group_id, $name, $notes, $query);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $report->id, 'oco.report.update', ['name'=>$name, 'notes'=>$notes, 'query'=>$query]);
	}
	public function moveReportToGroup($reportId, $groupId) {
		$report = $this->db->getReport($reportId);
		if(empty($report)) throw new ENotFoundxception();
		$reportGroup = $this->db->getReportGroup($groupId);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($report, PermissionManager::METHOD_WRITE);
		$this->systemUser->checkPermission($reportGroup, PermissionManager::METHOD_WRITE);

		$this->db->updateReport($report->id, intval($groupId), $report->name, $report->notes, $report->query);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $report->id, 'oco.report.move', ['group_id'=>$reportGroup->id]);
	}
	public function removeReport($id) {
		$report = $this->db->getReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->systemUser->checkPermission($report, PermissionManager::METHOD_DELETE);

		$result = $this->db->removeReport($report->id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $report->id, 'oco.report.delete', []);
		return $result;
	}
	public function createReportGroup($name, $parentGroupId=null) {
		if($parentGroupId == null) {
			$this->systemUser->checkPermission(new Models\ReportGroup(), PermissionManager::METHOD_CREATE);
		} else {
			$reportGroup = $this->db->getReportGroup($parentGroupId);
			if(empty($reportGroup)) throw new NotFoundException();
			$this->systemUser->checkPermission($reportGroup, PermissionManager::METHOD_CREATE);
		}

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$insertId = $this->db->addReportGroup($name, $parentGroupId);
		if(!$insertId) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $insertId, 'oco.report_group.create', ['name'=>$name, 'parent_report_group_id'=>$parentGroupId]);
		return $insertId;
	}
	public function renameReportGroup($id, $newName) {
		$reportGroup = $this->db->getReportGroup($id);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($reportGroup, PermissionManager::METHOD_WRITE);

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		$this->db->renameReportGroup($reportGroup->id, $newName);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $reportGroup->id, 'oco.report_group.update', ['name'=>$newName]);
	}
	public function removeReportGroup($id, $force=false) {
		$reportGroup = $this->db->getReportGroup($id);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->systemUser->checkPermission($reportGroup, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$subgroups = $this->db->getAllReportGroup($id);
			if(count($subgroups) > 0) throw new InvalidRequestException(LANG['delete_failed_subgroups']);
		}
		$result = $this->db->removeReportGroup($reportGroup->id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $reportGroup->id, 'oco.report_group.delete', []);
		return $result;
	}

	/*** Domain User Operations ***/
	public function getDomainUsers(Object $filterRessource=null) {
		if($filterRessource === null) {
			$this->systemUser->checkPermission(new Models\DomainUser(), PermissionManager::METHOD_READ);
			return $this->db->getAllDomainUser();
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getDomainUser($id) {
		$this->systemUser->checkPermission(new Models\DomainUser(), PermissionManager::METHOD_READ);

		$domainUser = $this->db->getDomainUser($id);
		if(empty($domainUser)) throw new NotFoundException();
		return $domainUser;
	}
	public function removeDomainUser($id) {
		$this->systemUser->checkPermission(new Models\DomainUser(), PermissionManager::METHOD_DELETE);

		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $id, 'oco.domain_user.delete', []);
		return $this->db->removeDomainUser($id);
	}

	/*** System User Operations ***/
	public function getSystemUsers(Object $filterRessource=null) {
		if($filterRessource === null) {
			$this->systemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
			return $this->db->getAllSystemUser();
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getSystemUserRoles() {
		$this->systemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
		return $this->db->getAllSystemUserRole();
	}
	public function getSystemUser($id) {
		$this->systemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
		$systemUser = $this->db->getSystemUser($id);
		if(empty($systemUser)) throw new NotFoundException();
		return $systemUser;
	}
	public function createSystemUser($username, $display_name, $description, $password, $roleId) {
		$this->systemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		if(empty(trim($username))
		|| empty(trim($display_name))) {
			throw new InvalidRequestException(LANG['name_cannot_be_empty']);
		}
		if(empty(trim($password))) {
			throw new InvalidRequestException(LANG['password_cannot_be_empty']);
		}
		if($this->db->getSystemUserByLogin($username) !== null) {
			throw new InvalidRequestException(LANG['username_already_exists']);
		}

		$insertId = $this->db->addSystemUser(
			md5(rand()), $username, $display_name,
			password_hash($password, PASSWORD_DEFAULT),
			0/*ldap*/, ''/*email*/, ''/*mobile*/, ''/*phone*/, $description, 0/*locked*/, $roleId
		);
		if(!$insertId) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $insertId, 'oco.system_user.create', [
			'username'=>$username,
			'display_name'=>$display_name,
			'description'=>$description,
			'system_user_role_id'=>$roleId
		]);
		return $insertId;
	}
	public function updateOwnSystemUserPassword($oldPassword, $newPassword) {
		if($this->systemUser->ldap) throw new Exception('Password of LDAP account cannot be modified');

		if(empty(trim($newPassword))) {
			throw new InvalidRequestException(LANG['password_cannot_be_empty']);
		}

		try {
			$authControl = new AuthenticationController($this->db);
			if(!$authControl->login($this->systemUser->username, $oldPassword)) {
				throw new AuthenticationException();
			}
		} catch(AuthenticationException $e) {
			throw new InvalidRequestException(LANG['old_password_is_not_correct']);
		}

		$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
		$this->db->updateSystemUser(
			$this->systemUser->id, $this->systemUser->uid, $this->systemUser->username, $this->systemUser->display_name, $newPasswordHash,
			$this->systemUser->ldap, $this->systemUser->email, $this->systemUser->phone, $this->systemUser->mobile, $this->systemUser->description, $this->systemUser->locked, $this->systemUser->system_user_role_id
		);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $this->systemUser->id, 'oco.system_user.update_password', []);
	}
	public function updateSystemUser($id, $username, $display_name, $description, $password, $roleId) {
		$this->systemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$u = $this->db->getSystemUser($id);
		if($u === null) throw new NotFoundException();
		if(!empty($u->ldap)) {
			$checkDescription = $u->description;
			if($checkDescription === null) $checkDescription = '';
			if($u->username !== $username
			|| $u->display_name !== $display_name
			|| $checkDescription !== $description
			|| $u->system_user_role_id !== $roleId
			|| !empty($password)) {
				throw new InvalidRequestException(LANG['ldap_accounts_cannot_be_modified']);
			}
		}

		if(empty(trim($username))) {
			throw new InvalidRequestException(LANG['username_cannot_be_empty']);
		}
		$checkUser = $this->db->getSystemUserByLogin($username);
		if($checkUser !== null && $checkUser->id !== $u->id) {
			throw new InvalidRequestException(LANG['username_already_exists']);
		}
		if(empty(trim($display_name))) {
			throw new InvalidRequestException(LANG['username_cannot_be_empty']);
		}
		$newPassword = $u->password;
		if(!empty($password)) {
			if(empty(trim($password))) {
				throw new InvalidRequestException(LANG['password_cannot_be_empty']);
			}
			$newPassword = password_hash($password, PASSWORD_DEFAULT);
		}

		$this->db->updateSystemUser(
			$u->id, $u->uid, trim($username), $display_name, $newPassword,
			$u->ldap, $u->email, $u->phone, $u->mobile, $description, $u->locked, $roleId
		);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $u->id, 'oco.system_user.update', [
			'username'=>$username,
			'display_name'=>$display_name,
			'description'=>$description,
			'system_user_role_id'=>$roleId
		]);
	}
	public function updateSystemUserLocked($id, $locked) {
		$this->systemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$u = $this->db->getSystemUser($id);
		if($u === null) throw new NotFoundException();

		$this->db->updateSystemUser(
			$u->id, $u->uid, $u->username, $u->display_name, $u->password,
			$u->ldap, $u->email, $u->phone, $u->mobile, $u->description, $locked, $u->system_user_role_id
		);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $u->id, 'oco.system_user.lock', ['locked'=>$locked]);
	}
	public function removeSystemUser($id) {
		$this->systemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$u = $this->db->getSystemUser($id);
		if($u === null) throw new NotFoundException();

		$result = $this->db->removeSystemUser($id);
		if(!$result) throw new Exception(LANG['unknown_error']);
		$this->db->addLogEntry(Models\Log::LEVEL_INFO, $this->systemUser->username, $u->id, 'oco.system_user.delete', []);
		return $result;
	}

}