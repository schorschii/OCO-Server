<?php

class CoreLogic {

	/*
		 Class CoreLogic
		 Database Abstraction Layer Wrapper

		 Adds additional (permission) checks & logic before the database is accessed and sanitizes user input.
		 It's public functions are used by the web frontend and the client API.

		Function naming (intentionally different from DatabaseController to easily distinguish if we operate directly with the database or with (permission) checks):
		 - prefix oriented on frontend (JS) command: get, create, edit, remove, add (for group memberships)
		 - entity name singular for single objects (e.g. "Computer") or plural if an array is returned (e.g. "Computers")
	*/

	private /*DatabaseController*/ $db;
	private /*Models\SystemUser*/ $su;
	private /*PermissionManager*/ $pm;

	function __construct($db, $systemUser=null) {
		$this->db = $db;
		$this->su = $systemUser;
	}

	/*** Permission Check Logic ***/
	public function checkPermission($ressource, String $method, Bool $throw=true) {
		// do not check permissions if CoreLogic is used in system context (e.g. cron jobs)
		if($this->su !== null && $this->su instanceof Models\SystemUser) {
			if($this->pm === null) $this->pm = new \PermissionManager($this->db, $this->su);
			$checkResult = $this->pm->hasPermission($ressource, $method);
			if(!$checkResult && $throw) throw new \PermissionException();
			return $checkResult;
		} else {
			return true;
		}
	}
	public function getPermissionEntry($ressource, String $method) {
		if($this->pm === null) $this->pm = new \PermissionManager($this->db, $this->su);
		return $this->pm->getPermissionEntry($ressource, $method);
	}

	/*** Computer Operations ***/
	public function getComputers(Object $filterRessource=null) {
		if($filterRessource === null) {
			$computersFiltered = [];
			foreach($this->db->selectAllComputer() as $computer) {
				if($this->checkPermission($computer, PermissionManager::METHOD_READ, false))
					$computersFiltered[] = $computer;
			}
			return $computersFiltered;
		} elseif($filterRessource instanceof Models\ComputerGroup) {
			$group = $this->db->selectComputerGroup($filterRessource->id);
			if(empty($group)) throw new NotFoundException();
			$this->checkPermission($group, PermissionManager::METHOD_READ);
			return $this->db->selectAllComputerByComputerGroupId($group->id);
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getComputerGroups($parentId=null) {
		$computerGroupsFiltered = [];
		foreach($this->db->selectAllComputerGroupByParentComputerGroupId($parentId) as $computerGroup) {
			if($this->checkPermission($computerGroup, PermissionManager::METHOD_READ, false))
				$computerGroupsFiltered[] = $computerGroup;
		}
		return $computerGroupsFiltered;
	}
	public function getComputer($id) {
		$computer = $this->db->selectComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_READ);
		return $computer;
	}
	public function getComputerGroup($id) {
		$computerGroup = $this->db->selectComputerGroup($id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->checkPermission($computerGroup, PermissionManager::METHOD_READ);
		return $computerGroup;
	}
	public function createComputer($hostname, $notes='', $agentKey='') {
		$this->checkPermission(new Models\Computer(), PermissionManager::METHOD_CREATE);

		$finalHostname = trim($hostname);
		if(empty($finalHostname)) {
			throw new InvalidRequestException(LANG('hostname_cannot_be_empty'));
		}
		if($this->db->selectComputerByHostname($finalHostname) !== null) {
			throw new InvalidRequestException(LANG('hostname_already_exists'));
		}
		$insertId = $this->db->insertComputer($finalHostname, ''/*Agent Version*/, []/*Networks*/, $notes, $agentKey, ''/*Server Key*/);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.computer.create', ['hostname'=>$finalHostname, 'notes'=>$notes]);
		return $insertId;
	}
	public function editComputer($id, $hostname, $notes) {
		$computer = $this->db->selectComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_WRITE);

		$finalHostname = trim($hostname);
		if(empty($finalHostname)) {
			throw new InvalidRequestException(LANG('hostname_cannot_be_empty'));
		}
		$checkComputer = $this->db->selectComputerByHostname($finalHostname);
		if($checkComputer !== null && intval($checkComputer->id) !== intval($id)) {
			throw new InvalidRequestException(LANG('hostname_already_exists'));
		}
		$result = $this->db->updateComputer($computer->id, $finalHostname, $notes);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computer->id, 'oco.computer.update', ['hostname'=>$finalHostname]);
		return $result;
	}
	public function editComputerForceUpdate($id, $newValue) {
		$computer = $this->db->selectComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_WRITE);

		$result = $this->db->updateComputerForceUpdate($computer->id, $newValue);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computer->id, 'oco.computer.update', ['force_update'=>$newValue]);
		return $result;
	}
	public function wolComputers($computerIds, $debugOutput=true) {
		$wolMacAdresses = [];
		foreach($computerIds as $id) {
			$computer = $this->db->selectComputer($id);
			if(empty($computer)) throw new NotFoundException();
			$this->checkPermission($computer, PermissionManager::METHOD_WOL);

			foreach($this->db->selectAllComputerNetworkByComputerId($computer->id) as $n) {
				if(empty($n->mac) || $n->mac == '-' || $n->mac == '?') continue;
				$wolMacAdresses[] = $n->mac;
			}
		}
		if(count($wolMacAdresses) == 0) {
			throw new Exception(LANG('no_mac_addresses_for_wol'));
		}
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, null, 'oco.computer.wol', $wolMacAdresses);
		WakeOnLan::wol($wolMacAdresses, $debugOutput);
		return true;
	}
	public function removeComputer($id, $force=false) {
		$computer = $this->db->selectComputer($id);
		if(empty($computer)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$jobs = $this->db->selectAllPendingJobByComputerId($id);
			if(count($jobs) > 0) throw new InvalidRequestException(LANG('delete_failed_active_jobs'));
		}
		$result = $this->db->deleteComputer($computer->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computer->id, 'oco.computer.delete', json_encode($computer));
		return $result;
	}
	public function createComputerGroup($name, $parentGroupId=null) {
		if($parentGroupId == null) {
			$this->checkPermission(new Models\ComputerGroup(), PermissionManager::METHOD_CREATE);
		} else {
			$computerGroup = $this->db->selectComputerGroup($parentGroupId);
			if(empty($computerGroup)) throw new NotFoundException();
			$this->checkPermission($computerGroup, PermissionManager::METHOD_CREATE);
		}

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$insertId = $this->db->insertComputerGroup($name, $parentGroupId);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.computer_group.create', ['name'=>$name, 'parent_computer_group_id'=>$parentGroupId]);
		return $insertId;
	}
	public function renameComputerGroup($id, $newName) {
		$computerGroup = $this->db->selectComputerGroup($id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$this->db->updateComputerGroup($computerGroup->id, $newName);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computerGroup->id, 'oco.computer_group.update', ['name'=>$newName]);
	}
	public function addComputerToGroup($computerId, $groupId) {
		$computer = $this->db->selectComputer($computerId);
		if(empty($computer)) throw new NotFoundException();
		$computerGroup = $this->db->selectComputerGroup($groupId);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_WRITE);
		$this->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);

		if(count($this->db->selectAllComputerByIdAndComputerGroupId($computer->id, $computerGroup->id)) == 0) {
			$this->db->insertComputerGroupMember($computer->id, $computerGroup->id);
			$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computerGroup->id, 'oco.computer_group.add_member', ['computer_id'=>$computer->id]);
			$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computer->id, 'oco.computer.add_to_group', ['computer_group_id'=>$computerGroup->id]);
		}
	}
	public function removeComputerFromGroup($computerId, $groupId) {
		$computer = $this->db->selectComputer($computerId);
		if(empty($computer)) throw new NotFoundException();
		$computerGroup = $this->db->selectComputerGroup($groupId);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);

		$this->db->deleteComputerGroupMember($computer->id, $computerGroup->id);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computerGroup->id, 'oco.computer_group.remove_member', ['computer_id'=>$computer->id]);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computer->id, 'oco.computer.remove_from_group', ['computer_group_id'=>$computerGroup->id]);
	}
	public function removeComputerGroup($id, $force=false) {
		$computerGroup = $this->db->selectComputerGroup($id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->checkPermission($computerGroup, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$subgroups = $this->db->selectAllComputerGroupByParentComputerGroupId($id);
			if(count($subgroups) > 0) throw new InvalidRequestException(LANG('delete_failed_subgroups'));
		}
		$result = $this->db->deleteComputerGroup($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $computerGroup->id, 'oco.computer_group.delete', []);
		return $result;
	}

	/*** Package Operations ***/
	public function getPackages(Object $filterRessource=null) {
		if($filterRessource === null) {
			$packagesFiltered = [];
			foreach($this->db->selectAllPackage() as $package) {
				if($this->checkPermission($package, PermissionManager::METHOD_READ, false))
					$packagesFiltered[] = $package;
			}
			return $packagesFiltered;
		} elseif($filterRessource instanceof Models\PackageFamily) {
			$family = $this->db->selectPackageFamily($filterRessource->id);
			if(empty($family)) throw new NotFoundException();
			$this->checkPermission($family, PermissionManager::METHOD_READ);
			return $this->db->selectAllPackageByPackageFamilyId($family->id);
		} elseif($filterRessource instanceof Models\PackageGroup) {
			$group = $this->db->selectPackageGroup($filterRessource->id);
			if(empty($group)) throw new NotFoundException();
			$this->checkPermission($group, PermissionManager::METHOD_READ);
			return $this->db->selectAllPackageByPackageGroupId($group->id);
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getPackageGroups($parentId=null) {
		$packageGroupsFiltered = [];
		foreach($this->db->selectAllPackageGroupByParentPackageGroupId($parentId) as $packageGroup) {
			if($this->checkPermission($packageGroup, PermissionManager::METHOD_READ, false))
				$packageGroupsFiltered[] = $packageGroup;
		}
		return $packageGroupsFiltered;
	}
	public function getPackageFamilies(Object $filterRessource=null, $binaryAsBase64=false) {
		if($filterRessource === null) {
			$packageFamiliesFiltered = [];
			foreach($this->db->selectAllPackageFamily($binaryAsBase64) as $packageFamily) {
				if($this->checkPermission($packageFamily, PermissionManager::METHOD_READ, false))
					$packageFamiliesFiltered[] = $packageFamily;
			}
			return $packageFamiliesFiltered;
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getPackage($id) {
		$package = $this->db->selectPackage($id);
		if(empty($package)) throw new NotFoundException();
		$this->checkPermission($package, PermissionManager::METHOD_READ);
		return $package;
	}
	public function getPackageGroup($id) {
		$packageGroup = $this->db->selectPackageGroup($id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($packageGroup, PermissionManager::METHOD_READ);
		return $packageGroup;
	}
	public function getPackageFamily($id) {
		$packageFamily = $this->db->selectPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->checkPermission($packageFamily, PermissionManager::METHOD_READ);
		return $packageFamily;
	}
	public function createPackage($name, $version, $description, $author,
		$installProcedure, $installProcedureSuccessReturnCodes, $installProcedurePostAction,
		$uninstallProcedure, $uninstallProcedureSuccessReturnCodes, $downloadForUninstall, $uninstallProcedurePostAction,
		$compatibleOs, $compatibleOsVersion, $tmpFilePath, $fileName=null) {

		$this->checkPermission(new Models\Package(), PermissionManager::METHOD_CREATE);

		if($fileName == null && $tmpFilePath != null) {
			$fileName = basename($tmpFilePath);
		}
		if(empty($name) || empty($installProcedure) || empty($version)) {
			throw new InvalidRequestException(LANG('please_fill_required_fields'));
		}
		if(!empty($this->db->selectAllPackageByPackageFamilyNameAndVersion($name, $version))) {
			throw new InvalidRequestException(LANG('package_exists_with_version'));
		}
		// decide what to do with uploaded file
		if($tmpFilePath != null && file_exists($tmpFilePath)) {
			$mimeType = mime_content_type($tmpFilePath);
			if($mimeType != 'application/zip') {
				// create zip with uploaded file if uploaded file is not a zip file
				$newTmpFilePath = '/tmp/ocotmparchive.zip';
				$zip = new ZipArchive();
				if(!$zip->open($newTmpFilePath, ZipArchive::CREATE)) {
					throw new Exception(LANG('cannot_create_zip_file'));
				}
				$zip->addFile($tmpFilePath, basename($fileName));
				$zip->close();
				$tmpFilePath = $newTmpFilePath;
			}
		}
		// insert into database
		$packageFamilyId = null;
		$existingPackageFamily = $this->db->selectPackageFamilyByName($name);
		if($existingPackageFamily === null) {
			$this->checkPermission(new Models\PackageFamily(), PermissionManager::METHOD_CREATE);

			$packageFamilyId = $this->db->insertPackageFamily($name, '');
		} else {
			$this->checkPermission($existingPackageFamily, PermissionManager::METHOD_CREATE);

			$packageFamilyId = $existingPackageFamily->id;
		}
		if(!$packageFamilyId) {
			throw new Exception(LANG('database_error'));
		}
		$insertId = $this->db->insertPackage(
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
			throw new Exception(LANG('database_error'));
		}
		// move file to payload dir
		if($tmpFilePath != null && file_exists($tmpFilePath)) {
			$finalFileName = intval($insertId).'.zip';
			$finalFilePath = PACKAGE_PATH.'/'.$finalFileName;
			$result = rename($tmpFilePath, $finalFilePath);
			if(!$result) {
				error_log('Can not move uploaded file to: '.$finalFilePath);
				$this->db->deletePackage($insertId);
				throw new Exception(LANG('cannot_move_uploaded_file'));
			}
		}
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.package.create', ['package_family_id'=>$packageFamilyId, 'version'=>$version]);
		return $insertId;
	}
	public function addPackageToGroup($packageId, $groupId) {
		$package = $this->db->selectPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$packageGroup = $this->db->selectPackageGroup($groupId);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($package, PermissionManager::METHOD_WRITE);
		$this->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		if(count($this->db->selectAllPackageByIdAndPackageGroupId($package->id, $packageGroup->id)) == 0) {
			$this->db->insertPackageGroupMember($package->id, $packageGroup->id);
			$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageGroup->id, 'oco.package_group.add_member', ['package_id'=>$package->id]);
			$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $package->id, 'oco.package.add_to_group', ['package_group_id'=>$packageGroup->id]);
		}
	}
	public function removePackageFromGroup($packageId, $groupId) {
		$package = $this->db->selectPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$packageGroup = $this->db->selectPackageGroup($groupId);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		$this->db->deletePackageFromGroup($package->id, $packageGroup->id);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageGroup->id, 'oco.package_group.remove_member', ['package_id'=>$package->id]);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $package->id, 'oco.package.remove_from_group', ['package_group_id'=>$packageGroup->id]);
	}
	public function removePackage($id, $force=false) {
		$package = $this->db->selectPackage($id);
		if(empty($package)) throw new NotFoundException();
		$this->checkPermission($package, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$jobs = $this->db->selectAllPendingJobByPackageId($id);
			if(count($jobs) > 0) throw new InvalidRequestException(LANG('delete_failed_active_jobs'));

			$dependentPackages = $this->db->selectAllPackageDependencyByDependentPackageId($id);
			if(count($dependentPackages) > 0) throw new InvalidRequestException(LANG('delete_failed_dependent_packages'));
		}

		$path = $package->getFilePath();
		if(!empty($path)) unlink($path);

		$result = $this->db->deletePackage($package->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $package->id, 'oco.package.delete', json_encode($package));
		return $result;
	}
	public function removePackageFamily($id) {
		$packageFamily = $this->db->selectPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->checkPermission($packageFamily, PermissionManager::METHOD_DELETE);

		$packages = $this->db->selectAllPackageByPackageFamilyId($id);
		if(count($packages) > 0) throw new InvalidRequestException(LANG('delete_failed_package_family_contains_packages'));

		$result = $this->db->deletePackageFamily($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageFamily->id, 'oco.package_family.delete', json_encode($packageFamily));
		return $result;
	}
	public function createPackageGroup($name, $parentGroupId=null) {
		if($parentGroupId == null) {
			$this->checkPermission(new Models\PackageGroup(), PermissionManager::METHOD_CREATE);
		} else {
			$packageGroup = $this->db->selectPackageGroup($parentGroupId);
			if(empty($packageGroup)) throw new NotFoundException();
			$this->checkPermission($packageGroup, PermissionManager::METHOD_CREATE);
		}

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$insertId = $this->db->insertPackageGroup($name, $parentGroupId);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.package_group.create', ['name'=>$name, 'parent_packge_group_id'=>$parentGroupId]);
		return $insertId;
	}
	public function removePackageGroup($id, $force=false) {
		$packageGroup = $this->db->selectPackageGroup($id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($packageGroup, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$subgroups = $this->db->selectAllPackageGroupByParentPackageGroupId($id);
			if(count($subgroups) > 0) throw new InvalidRequestException(LANG('delete_failed_subgroups'));
		}

		$result = $this->db->deletePackageGroup($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageGroup->id, 'oco.package_group.delete', []);
		return $result;
	}
	public function editPackageFamily($id, $name, $notes) {
		$packageFamily = $this->db->selectPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->checkPermission($packageFamily, PermissionManager::METHOD_WRITE);

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$this->db->updatePackageFamily($packageFamily->id, $name, $notes, $packageFamily->icon);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageFamily->id, 'oco.package_family.update', ['name'=>$name, 'notes'=>$notes]);
	}
	public function editPackageFamilyIcon($id, $icon) {
		$packageFamily = $this->db->selectPackageFamily($id);
		if(empty($packageFamily)) throw new NotFoundException();
		$this->checkPermission($packageFamily, PermissionManager::METHOD_WRITE);

		$this->db->updatePackageFamily($packageFamily->id, $packageFamily->name, $packageFamily->notes, $icon);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageFamily->id, 'oco.package_family.update', ['icon'=>base64_encode($icon)]);
	}
	public function addPackageDependency($packageId, $dependentPackageId) {
		$package = $this->db->selectPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$dependentPackage = $this->db->selectPackage($dependentPackageId);
		if(empty($dependentPackage)) throw new NotFoundException();
		$this->checkPermission($package, PermissionManager::METHOD_WRITE);
		$this->checkPermission($dependentPackage, PermissionManager::METHOD_WRITE);

		$this->db->insertPackageDependency($package->id, $dependentPackage->id);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $package->id, 'oco.package.add_dependency', ['dependent_package_id'=>$dependentPackage->id]);
	}
	public function removePackageDependency($packageId, $dependentPackageId) {
		$package = $this->db->selectPackage($packageId);
		if(empty($package)) throw new NotFoundException();
		$dependentPackage = $this->db->selectPackage($dependentPackageId);
		if(empty($dependentPackage)) throw new NotFoundException();
		$this->checkPermission($package, PermissionManager::METHOD_WRITE);
		$this->checkPermission($dependentPackage, PermissionManager::METHOD_WRITE);

		$this->db->deletePackageDependencyByPackageIdAndDependentPackageId($package->id, $dependentPackage->id);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $package->id, 'oco.package.remove_dependency', ['dependent_package_id'=>$dependentPackage->id]);
	}
	public function editPackage($id, $package_family_id, $version, $compatibleOs, $compatibleOsVersion, $notes, $installProcedure, $installProcedureSuccessReturnCodes, $installProcedurePostAction, $uninstallProcedure, $uninstallProcedureSuccessReturnCodes, $uninstallProcedurePostAction, $downloadForUninstall) {
		$package = $this->db->selectPackage($id);
		if(empty($package)) throw new NotFoundException();
		$this->checkPermission($package, PermissionManager::METHOD_WRITE);

		$package_family = $this->db->selectPackageFamily($package_family_id);
		if(empty($package_family)) throw new NotFoundException();
		$this->checkPermission($package_family, PermissionManager::METHOD_WRITE);

		if(empty(trim($version))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!is_numeric($installProcedurePostAction)
		|| !in_array($installProcedurePostAction, [Models\Package::POST_ACTION_NONE, Models\Package::POST_ACTION_RESTART, Models\Package::POST_ACTION_SHUTDOWN, Models\Package::POST_ACTION_EXIT])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(!is_numeric($uninstallProcedurePostAction)
		|| !in_array($uninstallProcedurePostAction, [Models\Package::POST_ACTION_NONE, Models\Package::POST_ACTION_RESTART, Models\Package::POST_ACTION_SHUTDOWN])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(intval($downloadForUninstall) !== 0 && intval($downloadForUninstall) !== 1) {
			throw new InvalidRequestException(LANG('invalid_input'));
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
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $package->id, 'oco.package.update', [
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
		$packageGroup = $this->db->selectPackageGroup($groupId);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		$this->db->reorderPackageInGroup($packageGroup->id, $oldPos, $newPos);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageGroup->id, 'oco.package_group.reorder', ['old_pos'=>$oldPos, 'new_pos'=>$newPos]);
	}
	public function renamePackageGroup($id, $newName) {
		$packageGroup = $this->db->selectPackageGroup($id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($packageGroup, PermissionManager::METHOD_DELETE);

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$this->db->updatePackageGroup($packageGroup->id, $newName);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $packageGroup->id, 'oco.package_group.update', ['name'=>$newName]);
	}

	/*** Deployment / Job Container Operations ***/
	public function getDeploymentRules(Object $filterRessource=null) {
		if($filterRessource === null) {
			$deploymentRulesFiltered = [];
			foreach($this->db->selectAllDeploymentRule() as $deploymentRule) {
				if($this->checkPermission($deploymentRule, PermissionManager::METHOD_READ, false))
					$deploymentRulesFiltered[] = $deploymentRule;
			}
			return $deploymentRulesFiltered;
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getDeploymentRule($id) {
		$deploymentRule = $this->db->selectDeploymentRule($id);
		if(empty($deploymentRule)) throw new NotFoundException();
		$this->checkPermission($deploymentRule, PermissionManager::METHOD_READ);
		return $deploymentRule;
	}
	public function evaluateDeploymentRule($id) {
		$deploymentRule = $this->db->selectDeploymentRule($id);
		if(empty($deploymentRule)) throw new NotFoundException();
		$this->checkPermission($deploymentRule, PermissionManager::METHOD_WRITE);
		return $this->db->evaluateDeploymentRule($deploymentRule->id);
	}
	public function createDeploymentRule($name, $notes, $author, $enabled, $computer_group_id, $package_group_id, $priority, $auto_uninstall) {
		$this->checkPermission(new Models\DeploymentRule(), PermissionManager::METHOD_CREATE);

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!in_array($enabled, ['0', '1'])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(!is_numeric($priority) || intval($priority) < -100 || intval($priority) > 100) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(!in_array($auto_uninstall, ['0', '1'])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		$computerGroup = $this->db->selectComputerGroup($computer_group_id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);
		$packageGroup = $this->db->selectPackageGroup($package_group_id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		$insertId = $this->db->insertDeploymentRule($name, $notes, $author, $enabled, $computer_group_id, $package_group_id, $priority, $auto_uninstall);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.deployment_rule.create', [
			'name'=>$name,
			'notes'=>$notes,
			'author'=>$author,
			'enabled'=>$enabled,
			'computer_group_id'=>$computer_group_id,
			'package_group_id'=>$package_group_id,
			'priority'=>$priority,
			'auto_uninstall'=>$auto_uninstall,
		]);
		return $insertId;
	}
	public function editDeploymentRule($id, $name, $notes, $enabled, $computer_group_id, $package_group_id, $priority, $auto_uninstall) {
		$dr = $this->db->selectDeploymentRule($id);
		if(empty($dr)) throw new NotFoundException();
		$this->checkPermission($dr, PermissionManager::METHOD_WRITE);

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!in_array($enabled, ['0', '1'])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(!is_numeric($priority) || intval($priority) < -100 || intval($priority) > 100) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(!in_array($auto_uninstall, ['0', '1'])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		$computerGroup = $this->db->selectComputerGroup($computer_group_id);
		if(empty($computerGroup)) throw new NotFoundException();
		$this->checkPermission($computerGroup, PermissionManager::METHOD_WRITE);
		$packageGroup = $this->db->selectPackageGroup($package_group_id);
		if(empty($packageGroup)) throw new NotFoundException();
		$this->checkPermission($packageGroup, PermissionManager::METHOD_WRITE);

		$this->db->updateDeploymentRule($dr->id, $name, $notes, $enabled, $computer_group_id, $package_group_id, $priority, $auto_uninstall);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $dr->id, 'oco.deployment_rule.update', [
			'name'=>$name,
			'notes'=>$notes,
			'enabled'=>$enabled,
			'computer_group_id'=>$computer_group_id,
			'package_group_id'=>$package_group_id,
			'priority'=>$priority,
			'auto_uninstall'=>$auto_uninstall,
		]);
	}
	public function renewDeploymentRuleJob($renewDeploymentRuleId, $renewJobIds) {
		$container = $this->db->selectDeploymentRule($renewDeploymentRuleId);
		if(!$container) throw new NotFoundException();
		$this->checkPermission($container, PermissionManager::METHOD_WRITE);
		$jobIds = [];
		foreach($this->db->selectAllDynamicJobByDeploymentRuleId($container->id) as $job) {
			if(!empty($renewJobIds) && !in_array($job->id, $renewJobIds)) continue;
			if($job->isFailed()) $jobIds[] = $job->id;
		}
		if(!$this->db->updateDynamicJob($jobIds, Models\Job::STATE_WAITING_FOR_AGENT, null/*return_code*/, ''/*messgae*/))
			throw new InvalidRequestException(LANG('no_failed_jobs'));
	}
	public function removeDeploymentRule($id) {
		$dr = $this->db->selectDeploymentRule($id);
		if(empty($dr)) throw new NotFoundException();
		$this->checkPermission($dr, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteDeploymentRule($dr->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $dr->id, 'oco.deployment_rule.delete', json_encode($dr));
		return $result;
	}
	public function getJobContainers($selfService) {
		$jobContainersFiltered = [];
		foreach($this->db->selectAllJobContainer($selfService) as $jobContainer) {
			if($this->checkPermission($jobContainer, PermissionManager::METHOD_READ, false))
				$jobContainersFiltered[] = $jobContainer;
		}
		return $jobContainersFiltered;
	}
	public function getJobContainer($id) {
		$jobContainer = $this->db->selectJobContainer($id);
		if(empty($jobContainer)) throw new NotFoundException();
		$this->checkPermission($jobContainer, PermissionManager::METHOD_READ);
		return $jobContainer;
	}
	public function deploy($name, $description, $author, $computerIds, $computerGroupIds, $computerReportIds, $packageIds, $packageGroupIds, $packageReportIds, $dateStart, $dateEnd, $useWol, $shutdownWakedAfterCompletion, $restartTimeout, $autoCreateUninstallJobs, $forceInstallSameVersion, $sequenceMode, $priority, $constraintIpRanges=[], $selfService=0) {
		$this->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE);

		// check user input
		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(empty($restartTimeout) || empty($dateStart) || strtotime($dateStart) === false) {
			throw new InvalidRequestException(LANG('please_fill_required_fields'));
		}
		if(!empty($dateEnd) // check end date if not empty
		&& (strtotime($dateEnd) === false || strtotime($dateStart) >= strtotime($dateEnd))
		) {
			throw new InvalidRequestException(LANG('end_time_before_start_time'));
		}
		if($sequenceMode != Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED
		&& $sequenceMode != Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if($priority < -100 || $priority > 100) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}

		// check if given IDs exists and add them to a consolidated array
		$computer_ids = [];
		$packages = [];
		$computer_group_ids = [];
		$package_group_ids = [];
		$computer_report_ids = [];
		$package_report_ids = [];
		if(!empty($computerIds)) foreach($computerIds as $computer_id) {
			$tmpComputer = $this->db->selectComputer($computer_id);
			if($tmpComputer == null) continue;
			if(!$this->checkPermission($tmpComputer, PermissionManager::METHOD_DEPLOY, false)) continue;
			$computer_ids[$computer_id] = $computer_id;
		}
		if(!empty($computerGroupIds)) foreach($computerGroupIds as $computer_group_id) {
			$tmpComputerGroup = $this->db->selectComputerGroup($computer_group_id);
			if($tmpComputerGroup == null) continue;
			$computer_group_ids[] = $computer_group_id;
		}
		if(!empty($computerReportIds)) foreach($computerReportIds as $computer_report_id) {
			$tmpComputerReport = $this->db->selectReport($computer_report_id);
			if($tmpComputerReport == null) continue;
			$computer_report_ids[] = $computer_report_id;
		}

		if(!empty($packageIds)) foreach($packageIds as $package_id) {
			$packages = $packages + $this->compileDeployPackageArray($package_id);
		}
		if(!empty($packageGroupIds)) foreach($packageGroupIds as $package_group_id) {
			$tmpPackageGroup = $this->db->selectPackageGroup($package_group_id);
			if($tmpPackageGroup == null) continue;
			$package_group_ids[] = $package_group_id;
		}
		if(!empty($packageReportIds)) foreach($packageReportIds as $package_report_id) {
			$tmpPackageReport = $this->db->selectReport($package_report_id);
			if($tmpPackageReport == null) continue;
			$package_report_ids[] = $package_report_id;
		}

		// add all group members
		foreach($computer_group_ids as $computer_group_id) {
			foreach($this->db->selectAllComputerByComputerGroupId($computer_group_id) as $c) {
				if(!$this->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
				$computer_ids[$c->id] = $c->id;
			}
		}
		foreach($package_group_ids as $package_group_id) {
			foreach($this->db->selectAllPackageByPackageGroupId($package_group_id) as $p) {
				$packages = $packages + $this->compileDeployPackageArray($p->id);
			}
		}

		// add all report results
		foreach($computer_report_ids as $computer_report_id) {
			//try { // ignore report with syntax errors
				foreach($this->executeReport($computer_report_id) as $row) {
					$c = $this->db->selectComputer($row['computer_id']);
					if(empty($c) || !$this->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
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
					$p = $this->db->selectPackage($row['package_id']);
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
			throw new InvalidRequestException(LANG('no_jobs_created'));
		}

		// wol handling
		$wolSent = -1;
		if($useWol) {
			if(strtotime($dateStart) <= time()) {
				// instant WOL if start time is already in the past
				$wolSent = 1;
				$wolMacAdresses = [];
				foreach($computer_ids as $cid) {
					foreach($this->db->selectAllComputerNetworkByComputerId($cid) as $cn) {
						$wolMacAdresses[] = $cn->mac;
					}
				}
				WakeOnLan::wol($wolMacAdresses, false);
			} else {
				$wolSent = 0;
			}
		}

		// create jobs
		if($jcid = $this->db->insertJobContainer(
			$name, $author,
			empty($dateStart) ? date('Y-m-d H:i:s') : $dateStart,
			empty($dateEnd) ? null : $dateEnd,
			$description,
			$wolSent, $shutdownWakedAfterCompletion,
			$sequenceMode, $priority, $this->compileIpRanges($constraintIpRanges),
			$selfService
		)) {
			foreach($computer_ids as $computer_id) {

				$tmpComputer = $this->db->selectComputer($computer_id);
				$createdUninstallJobs = [];
				$sequence = 1;

				foreach($packages as $pid => $package) {

					$targetJobState = Models\Job::STATE_WAITING_FOR_AGENT;

					// check OS compatibility
					if(!empty($package['compatible_os']) && !empty($tmpComputer->os)
					&& $package['compatible_os'] != $tmpComputer->os) {
						// create failed job
						if($this->db->insertStaticJob($jcid, $computer_id,
							$pid, $package['procedure'], $package['success_return_codes'],
							0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
							$package['install_procedure_post_action'], $restartTimeout,
							$sequence, Models\Job::STATE_OS_INCOMPATIBLE
						)) {
							$sequence ++;
						}
						continue;
					}
					if(!empty($package['compatible_os_version']) && !empty($tmpComputer->os_version)
					&& $package['compatible_os_version'] != $tmpComputer->os_version) {
						// create failed job
						if($this->db->insertStaticJob($jcid, $computer_id,
							$pid, $package['procedure'], $package['success_return_codes'],
							0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
							$package['install_procedure_post_action'], $restartTimeout,
							$sequence, Models\Job::STATE_OS_INCOMPATIBLE
						)) {
							$sequence ++;
						}
						continue;
					}

					foreach($this->db->selectAllComputerPackageByComputerId($computer_id) as $cp) {
						// ignore if it is an automatically added dependency package and version is the same as already installed
						if($package['is_dependency'] && strval($pid) === $cp->package_id) continue 2;

						// if the same version is already installed, add a informative job with status STATE_ALREADY_INSTALLED
						if($cp->package_id === strval($pid) && empty($forceInstallSameVersion)) {
							$targetJobState = Models\Job::STATE_ALREADY_INSTALLED;
							break;
						}

						// if requested, automagically create uninstall jobs
						if(!empty($autoCreateUninstallJobs)) {
							// uninstall it, if it is from the same package family ...
							if($cp->package_family_id === $package['package_family_id']) {
								$cpp = $this->db->selectPackage($cp->package_id, null);
								if($cpp == null) continue;
								// ... but not, if it is another package family version and autoCreateUninstallJobs flag is set
								if($cp->package_id !== strval($pid) && empty($autoCreateUninstallJobs)) continue;
								// ... and not, if this uninstall job was already created
								if(in_array($cp->id, $createdUninstallJobs)) continue;
								$createdUninstallJobs[] = $cp->id;
								$this->db->insertStaticJob($jcid, $computer_id,
									$cpp->id, $cpp->uninstall_procedure, $cpp->uninstall_procedure_success_return_codes,
									1/*is_uninstall*/, ($cpp->download_for_uninstall&&$cpp->getFilePath()) ? 1 : 0,
									$cpp->uninstall_procedure_post_action, $restartTimeout,
									$sequence, Models\Job::STATE_WAITING_FOR_AGENT
								);
								$sequence ++;
							}
						}
					}

					// create installation job
					if($this->db->insertStaticJob($jcid, $computer_id,
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

		$jobs = $this->db->selectAllStaticJobByJobContainer($jcid);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $author, $jcid, 'oco.job_container.create', ['name'=>$name, 'jobs'=>$jobs]);
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
	private function compileDeployPackageArray($packageId, $isDependency=false, $existingPackages=[]) {
		$packages = [];

		// check if id exists
		$p = $this->db->selectPackage($packageId, null);
		if($p == null) return [];

		// check permission
		if(!$this->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) return [];

		// recursive dependency resolver
		foreach($this->db->selectAllPackageDependencyByPackageId($p->id) as $p2) {
			if(array_key_exists($p2->id, $existingPackages)) continue;
			$packages = $packages + $this->compileDeployPackageArray($p2->id, true, array_merge([$p2->id=>$p2], $existingPackages));
		}

		// add requested package after dependencies
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
		$this->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE);

		// check user input
		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(empty($restartTimeout) || empty($dateStart) || strtotime($dateStart) === false) {
			throw new InvalidRequestException(LANG('please_fill_required_fields'));
		}
		if(!empty($dateEnd) // check end date if not empty
		&& (strtotime($dateEnd) === false || strtotime($dateStart) >= strtotime($dateEnd))
		) {
			throw new InvalidRequestException(LANG('end_time_before_start_time'));
		}
		if($sequenceMode != Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED
		&& $sequenceMode != Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if($priority < -100 || $priority > 100) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}

		// wol handling
		$computer_ids = [];
		foreach($installationIds as $id) {
			$ap = $this->db->selectComputerPackage($id);
			if(empty($ap)) continue;
			$tmpComputer = $this->db->selectComputer($ap->computer_id);
			if(empty($tmpComputer)) continue;
			if(!$this->checkPermission($tmpComputer, PermissionManager::METHOD_DEPLOY, false)) continue;

			$computer_ids[] = $ap->computer_id;
		}
		$wolSent = -1;
		if($useWol) {
			if(strtotime($dateStart) <= time()) {
				// instant WOL if start time is already in the past
				$wolSent = 1;
				$wolMacAdresses = [];
				foreach($computer_ids as $cid) {
					foreach($this->db->selectAllComputerNetworkByComputerId($cid) as $cn) {
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
			throw new InvalidRequestException(LANG('no_jobs_created'));
		}

		// create jobs
		$jobs = [];
		$jcid = $this->db->insertJobContainer(
			$name, $author,
			empty($dateStart) ? date('Y-m-d H:i:s') : $dateStart,
			empty($dateEnd) ? null : $dateEnd,
			$description, $wolSent, $shutdownWakedAfterCompletion,
			$sequenceMode, $priority, $this->compileIpRanges($constraintIpRanges),
			0/*self-service*/
		);
		foreach($installationIds as $id) {
			$ap = $this->db->selectComputerPackage($id); if(empty($ap)) continue;
			$p = $this->db->selectPackage($ap->package_id); if(empty($p)) continue;
			$c = $this->db->selectComputer($ap->computer_id); if(empty($c)) continue;

			if(!$this->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
			if(!$this->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;

			$jid = $this->db->insertStaticJob($jcid, $ap->computer_id,
				$ap->package_id, $p->uninstall_procedure, $p->uninstall_procedure_success_return_codes,
				1/*is_uninstall*/, ($p->download_for_uninstall&&$p->getFilePath()) ? 1 : 0,
				$p->uninstall_procedure_post_action, $restartTimeout,
				0/*sequence*/
			);
			$jobs[] = $this->db->selectStaticJob($jid);
		}
		// if instant WOL: check if computers are currently online (to know if we should shut them down after all jobs are done)
		if(strtotime($dateStart) <= time() && $useWol && $shutdownWakedAfterCompletion) {
			$this->db->setComputerOnlineStateForWolShutdown($jcid);
		}

		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $jcid, 'oco.job_container.create', ['name'=>$name, 'jobs'=>$jobs]);
	}
	public function removeComputerAssignedPackage($id) {
		$computerPackageAssignment = $this->db->selectComputerPackage($id);
		if(!$computerPackageAssignment) throw new NotFoundException();
		$computer = $this->db->selectComputer($computerPackageAssignment->computer_id);
		if(!$computer) throw new NotFoundException();
		$package = $this->db->selectPackage($computerPackageAssignment->package_id);
		if(!$package) throw new NotFoundException();

		$this->checkPermission($computer, PermissionManager::METHOD_WRITE);
		$this->checkPermission($package, PermissionManager::METHOD_WRITE);

		$result = $this->db->deleteComputerPackage($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $package->id, 'oco.package.remove_assignment', ['computer_id'=>$computer->id]);
		return $result;
	}
	public function renewFailedStaticJobsInJobContainer($name, $description, $author, $renewJobContainerId, $renewJobIds, $dateStart, $dateEnd, $useWol, $shutdownWakedAfterCompletion, $sequenceMode=0, $priority=0) {
		$container = $this->db->selectJobContainer($renewJobContainerId);
		if(!$container) throw new NotFoundException();
		$this->checkPermission($container, PermissionManager::METHOD_WRITE);
		$this->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE);

		// check user input
		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(empty($dateStart) || strtotime($dateStart) === false) {
			throw new InvalidRequestException(LANG('please_fill_required_fields'));
		}
		if(!empty($dateEnd) // check end date if not empty
		&& (strtotime($dateEnd) === false || strtotime($dateStart) >= strtotime($dateEnd))
		) {
			throw new InvalidRequestException(LANG('end_time_before_start_time'));
		}
		if($sequenceMode != Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED
		&& $sequenceMode != Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if($priority < -100 || $priority > 100) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}

		// wol handling
		$computer_ids = [];
		foreach($this->db->selectAllStaticJobByJobContainer($container->id) as $job) {
			if(!empty($renewJobIds) && !in_array($job->id, $renewJobIds)) continue;
			if($job->isFailed()) {
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
					foreach($this->db->selectAllComputerNetworkByComputerId($cid) as $cn) {
						$wolMacAdresses[] = $cn->mac;
					}
				}
				WakeOnLan::wol($wolMacAdresses, false);
			} else {
				$wolSent = 0;
			}
		}

		// create new jobs
		$jobs = [];
		if($jcid = $this->db->insertJobContainer(
			$name, $author,
			$dateStart, empty($dateEnd) ? null : $dateEnd,
			$description, $wolSent, $shutdownWakedAfterCompletion,
			$sequenceMode, $priority, $container->agent_ip_ranges,
			0/*self-service*/
		)) {
			foreach($this->db->selectAllStaticJobByJobContainer($container->id) as $job) {
				if(!empty($renewJobIds) && !in_array($job->id, $renewJobIds)) continue;
				if($job->isFailed()) {

					// use the current package procedure, return codes, post action
					$package = $this->db->selectPackage($job->package_id);
					$newJob = new Models\StaticJob();
					$newJob->job_container_id = $jcid;
					$newJob->computer_id = $job->computer_id;
					$newJob->package_id = $job->package_id;
					$newJob->procedure = empty($job->is_uninstall) ? $package->install_procedure : $package->uninstall_procedure;
					$newJob->success_return_codes = empty($job->is_uninstall) ? $package->install_procedure_success_return_codes : $package->uninstall_procedure_success_return_codes;
					$newJob->is_uninstall = $job->is_uninstall;
					$newJob->download = $package->getFilePath() ? 1 : 0;
					$newJob->post_action = empty($job->is_uninstall) ? $package->install_procedure_post_action : $package->uninstall_procedure_post_action;
					$newJob->post_action_timeout = $job->post_action_timeout;
					$newJob->sequence = $job->sequence;

					if($this->db->insertStaticJob($newJob->job_container_id,
						$newJob->computer_id, $newJob->package_id,
						$newJob->procedure, $newJob->success_return_codes,
						$newJob->is_uninstall, $newJob->download,
						$newJob->post_action, $newJob->post_action_timeout,
						$newJob->sequence
					)) {
						$jobs[] = $newJob;
						$this->db->deleteStaticJob($job->id);
					}
				}
			}

			// check if there are any computer & packages
			if(count($jobs) == 0) {
				$this->db->deleteJobContainer($jcid);
				throw new InvalidRequestException(LANG('no_jobs_created'));
			}

			// if instant WOL: check if computers are currently online (to know if we should shut them down after all jobs are done)
			if(strtotime($dateStart) <= time() && $useWol && $shutdownWakedAfterCompletion) {
				$this->db->setComputerOnlineStateForWolShutdown($jcid);
			}

			$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $jcid, 'oco.job_container.create', ['name'>=$name, 'jobs'=>$jobs]);
		}
	}
	public function editJobContainer($id, $name, $enabled, $start_time, $end_time, $notes, $sequence_mode, $priority, $agent_ip_ranges) {
		$jc = $this->db->selectJobContainer($id);
		if(empty($jc)) throw new NotFoundException();
		$this->checkPermission($jc, PermissionManager::METHOD_WRITE);

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!in_array($enabled, ['0', '1'])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(DateTime::createFromFormat('Y-m-d H:i:s', $start_time) === false) {
			throw new InvalidRequestException(LANG('date_parse_error'));
		}
		if(!empty($end_time) && DateTime::createFromFormat('Y-m-d H:i:s', $end_time) === false) {
			throw new InvalidRequestException(LANG('date_parse_error'));
		}
		if(empty($end_time)) {
			$end_time = null;
		} else {
			if(strtotime($jc->start_time) > strtotime($end_time)) {
				throw new InvalidRequestException(LANG('end_time_before_start_time'));
			}
		}
		if(!is_numeric($sequence_mode)
		|| !in_array($sequence_mode, [Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED, Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED])) {
			throw new InvalidRequestException(LANG('invalid_input'));
		}
		if(!is_numeric($priority) || intval($priority) < -100 || intval($priority) > 100) {
			throw new InvalidRequestException(LANG('invalid_input'));
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
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $jc->id, 'oco.job_container.update', [
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
	public function moveStaticJobToJobContainer($jobId, $containerId) {
		$job = $this->db->selectStaticJob($jobId);
		if(empty($job) || !$job instanceof Models\StaticJob) throw new NotFoundException();
		$oldContainer = $this->db->selectJobContainer($job->job_container_id);
		if(empty($oldContainer)) throw new NotFoundException();
		$newContainer = $this->db->selectJobContainer($containerId);
		if(empty($newContainer)) throw new NotFoundException();
		$this->checkPermission($oldContainer, PermissionManager::METHOD_DELETE);
		$this->checkPermission($oldContainer, PermissionManager::METHOD_WRITE);
		$this->checkPermission($newContainer, PermissionManager::METHOD_WRITE);

		$this->db->moveStaticJobToJobContainer($job->id, $newContainer->id);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $newContainer->id, 'oco.job_container.move_jobs', [
			'old_container_id'=>$oldContainer->id,
			'new_container_id'=>$newContainer->id,
			'job_id'=>$job->id,
		]);
	}
	public function removeJobContainer($id) {
		$jc = $this->db->selectJobContainer($id);
		if(empty($jc)) throw new NotFoundException();
		$this->checkPermission($jc, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteJobContainer($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $jc->id, 'oco.job_container.delete', json_encode($jc));
		return $result;
	}
	public function removeStaticJob($id) {
		$job = $this->db->selectStaticJob($id);
		if(empty($job) || !$job instanceof Models\StaticJob) throw new NotFoundException();
		$jc = $this->db->selectJobContainer($job->job_container_id);
		if(empty($jc)) throw new NotFoundException();
		$this->checkPermission($jc, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteStaticJob($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $job->id, 'oco.job_container.job.delete', ['job_container_id'=>$jc->id]);
		return $result;
	}

	/*** Report Operations ***/
	public function getReports(Object $filterRessource=null) {
		if($filterRessource === null) {
			$reportsFiltered = [];
			foreach($this->db->selectAllReport() as $report) {
				if($this->checkPermission($report, PermissionManager::METHOD_READ, false))
					$reportsFiltered[] = $report;
			}
			return $reportsFiltered;
		} elseif($filterRessource instanceof Models\ReportGroup) {
			$group = $this->db->selectReportGroup($filterRessource->id);
			if(empty($group)) throw new NotFoundException();
			$this->checkPermission($group, PermissionManager::METHOD_READ);
			return $this->db->selectAllReportByReportGroupId($group->id);
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getReportGroups($parentId=null) {
		$reportGroupsFiltered = [];
		foreach($this->db->selectAllReportGroupByParentReportGroupId($parentId) as $reportGroup) {
			if($this->checkPermission($reportGroup, PermissionManager::METHOD_READ, false))
				$reportGroupsFiltered[] = $reportGroup;
		}
		return $reportGroupsFiltered;
	}
	public function getReport($id) {
		$report = $this->db->selectReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->checkPermission($report, PermissionManager::METHOD_READ);
		return $report;
	}
	public function executeReport($id) {
		$report = $this->db->selectReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->checkPermission($report, PermissionManager::METHOD_READ);
		return $this->db->executeReport($report->id);
	}
	public function getReportGroup($id) {
		$reportGroup = $this->db->selectReportGroup($id);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->checkPermission($reportGroup, PermissionManager::METHOD_READ);
		return $reportGroup;
	}
	public function createReport($name, $notes, $query, $groupId=0) {
		$this->checkPermission(new Models\Report(), PermissionManager::METHOD_CREATE);
		if(!empty($groupId)) {
			$reportGroup = $this->db->selectReportGroup($groupId);
			if(empty($reportGroup)) throw new NotFoundException();
			$this->checkPermission($reportGroup, PermissionManager::METHOD_WRITE);
		}

		if(empty(trim($name)) || empty(trim($query))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$insertId = $this->db->insertReport($groupId, $name, $notes, $query);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.report.create', ['name'=>$name, 'notes'=>$notes, 'query'=>$query, 'report_group_id'=>$groupId]);
		return $insertId;
	}
	public function editReport($id, $name, $notes, $query) {
		$report = $this->db->selectReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->checkPermission($report, PermissionManager::METHOD_WRITE);

		if(empty(trim($name)) || empty(trim($query))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$this->db->updateReport($report->id, $report->report_group_id, $name, $notes, $query);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $report->id, 'oco.report.update', ['name'=>$name, 'notes'=>$notes, 'query'=>$query]);
	}
	public function moveReportToGroup($reportId, $groupId) {
		$report = $this->db->selectReport($reportId);
		if(empty($report)) throw new ENotFoundxception();
		$reportGroup = $this->db->selectReportGroup($groupId);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->checkPermission($report, PermissionManager::METHOD_WRITE);
		$this->checkPermission($reportGroup, PermissionManager::METHOD_WRITE);

		$this->db->updateReport($report->id, intval($groupId), $report->name, $report->notes, $report->query);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $report->id, 'oco.report.move', ['group_id'=>$reportGroup->id]);
	}
	public function removeReport($id) {
		$report = $this->db->selectReport($id);
		if(empty($report)) throw new NotFoundException();
		$this->checkPermission($report, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteReport($report->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $report->id, 'oco.report.delete', json_encode($report));
		return $result;
	}
	public function createReportGroup($name, $parentGroupId=null) {
		if($parentGroupId == null) {
			$this->checkPermission(new Models\ReportGroup(), PermissionManager::METHOD_CREATE);
		} else {
			$reportGroup = $this->db->selectReportGroup($parentGroupId);
			if(empty($reportGroup)) throw new NotFoundException();
			$this->checkPermission($reportGroup, PermissionManager::METHOD_CREATE);
		}

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$insertId = $this->db->insertReportGroup($name, $parentGroupId);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.report_group.create', ['name'=>$name, 'parent_report_group_id'=>$parentGroupId]);
		return $insertId;
	}
	public function renameReportGroup($id, $newName) {
		$reportGroup = $this->db->selectReportGroup($id);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->checkPermission($reportGroup, PermissionManager::METHOD_WRITE);

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$this->db->updateReportGroup($reportGroup->id, $newName);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $reportGroup->id, 'oco.report_group.update', ['name'=>$newName]);
	}
	public function removeReportGroup($id, $force=false) {
		$reportGroup = $this->db->selectReportGroup($id);
		if(empty($reportGroup)) throw new NotFoundException();
		$this->checkPermission($reportGroup, PermissionManager::METHOD_DELETE);

		if(!$force) {
			$subgroups = $this->db->selectAllReportGroupByParentReportGroupId($id);
			if(count($subgroups) > 0) throw new InvalidRequestException(LANG('delete_failed_subgroups'));
		}
		$result = $this->db->deleteReportGroup($reportGroup->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $reportGroup->id, 'oco.report_group.delete', []);
		return $result;
	}

	/*** Domain User Operations ***/
	public function getDomainUserRoles() {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
		return $this->db->selectAllDomainUserRole();
	}
	public function createDomainUserRole($name, $permissions) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		if(empty(trim($name))
		|| empty(trim($permissions))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!json_decode($permissions)) {
			throw new InvalidRequestException(LANG('json_syntax_error'));
		}

		$insertId = $this->db->insertDomainUserRole($name, $permissions);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.domain_user_role.create', [
			'name'=>$name,
			'permissions'=>$permissions,
		]);
		return $insertId;
	}
	public function editDomainUserRole($id, $name, $permissions) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$r = $this->db->selectDomainUserRole($id);
		if($r === null) throw new NotFoundException();

		if(empty(trim($name))
		|| empty(trim($permissions))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!json_decode($permissions)) {
			throw new InvalidRequestException(LANG('json_syntax_error'));
		}

		$this->db->updateDomainUserRole($r->id, $name, $permissions);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $r->id, 'oco.domain_user_role.update', [
			'name'=>$name,
			'permissions'=>$permissions,
		]);
	}
	public function removeDomainUserRole($id) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$r = $this->db->selectDomainUserRole($id);
		if($r === null) throw new NotFoundException();
		if($r->system_user_count > 0) {
			throw new InvalidRequestException(LANG('role_cannot_be_deleted_still_assigned'));
		}

		$result = $this->db->deleteDomainUserRole($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $r->id, 'oco.domain_user_role.delete', []);
		return $result;
	}
	public function getDomainUsers(Object $filterRessource=null) {
		if($filterRessource === null) {
			$this->checkPermission(new Models\DomainUser(), PermissionManager::METHOD_READ);
			return $this->db->selectAllDomainUser();
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getDomainUser($id) {
		$this->checkPermission(new Models\DomainUser(), PermissionManager::METHOD_READ);

		$domainUser = $this->db->selectDomainUser($id);
		if(empty($domainUser)) throw new NotFoundException();
		return $domainUser;
	}
	public function editDomainUser($id, $password, $domainUserRoleId) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$u = $this->db->selectDomainUser($id);
		if($u === null) throw new NotFoundException();
		if(!empty($u->ldap)) {
			if($u->domain_user_role_id !== $domainUserRoleId
			|| !empty($password)) {
				throw new InvalidRequestException(LANG('ldap_accounts_cannot_be_modified'));
			}
		}

		$newPassword = $u->password;
		if(!empty($password)) {
			if(empty(trim($password))) {
				throw new InvalidRequestException(LANG('password_cannot_be_empty'));
			}
			$newPassword = password_hash($password, PASSWORD_DEFAULT);
		}

		$this->db->updateDomainUser($u->id, empty($domainUserRoleId)?null:$domainUserRoleId, $newPassword, $u->ldap);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $u->id, 'oco.domain_user.update', [
			'domain_user_role_id'=>$domainUserRoleId
		]);
	}
	public function removeDomainUser($id) {
		$du = $this->db->selectDomainUser($id);
		$this->checkPermission(new Models\DomainUser(), PermissionManager::METHOD_DELETE);

		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $id, 'oco.domain_user.delete', json_encode($du));
		return $this->db->deleteDomainUser($id);
	}

	/*** System User Operations ***/
	public function getSystemUserRoles() {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
		return $this->db->selectAllSystemUserRole();
	}
	public function createSystemUserRole($name, $permissions) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		if(empty(trim($name))
		|| empty(trim($permissions))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!json_decode($permissions)) {
			throw new InvalidRequestException(LANG('json_syntax_error'));
		}

		$insertId = $this->db->insertSystemUserRole($name, $permissions);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.system_user_role.create', [
			'name'=>$name,
			'permissions'=>$permissions,
		]);
		return $insertId;
	}
	public function editSystemUserRole($id, $name, $permissions) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$r = $this->db->selectSystemUserRole($id);
		if($r === null) throw new NotFoundException();

		if(empty(trim($name))
		|| empty(trim($permissions))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(!json_decode($permissions)) {
			throw new InvalidRequestException(LANG('json_syntax_error'));
		}

		$this->db->updateSystemUserRole($r->id, $name, $permissions);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $r->id, 'oco.system_user_role.update', [
			'name'=>$name,
			'permissions'=>$permissions,
		]);
	}
	public function removeSystemUserRole($id) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$r = $this->db->selectSystemUserRole($id);
		if($r === null) throw new NotFoundException();
		if($r->system_user_count > 0) {
			throw new InvalidRequestException(LANG('role_cannot_be_deleted_still_assigned'));
		}

		$result = $this->db->deleteSystemUserRole($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $r->id, 'oco.system_user_role.delete', []);
		return $result;
	}

	public function getSystemUsers(Object $filterRessource=null) {
		if($filterRessource === null) {
			$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
			return $this->db->selectAllSystemUser();
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getSystemUser($id) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
		$systemUser = $this->db->selectSystemUser($id);
		if(empty($systemUser)) throw new NotFoundException();
		return $systemUser;
	}
	public function createSystemUser($username, $display_name, $description, $password, $systemUserRoleId) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		if(empty(trim($username))
		|| empty(trim($display_name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		if(empty(trim($password))) {
			throw new InvalidRequestException(LANG('password_cannot_be_empty'));
		}
		if($this->db->selectSystemUserByUsername($username) !== null) {
			throw new InvalidRequestException(LANG('username_already_exists'));
		}

		$insertId = $this->db->insertSystemUser(
			md5(rand()), $username, $display_name,
			password_hash($password, PASSWORD_DEFAULT),
			0/*ldap*/, ''/*email*/, ''/*mobile*/, ''/*phone*/, $description, 0/*locked*/, $systemUserRoleId
		);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.system_user.create', [
			'username'=>$username,
			'display_name'=>$display_name,
			'description'=>$description,
			'system_user_role_id'=>$systemUserRoleId
		]);
		return $insertId;
	}
	public function editOwnSystemUserPassword($oldPassword, $newPassword) {
		if($this->su->ldap) throw new Exception('Password of LDAP account cannot be modified');

		if(empty(trim($newPassword))) {
			throw new InvalidRequestException(LANG('password_cannot_be_empty'));
		}

		try {
			$authControl = new AuthenticationController($this->db);
			if(!$authControl->login($this->su->username, $oldPassword)) {
				throw new AuthenticationException();
			}
		} catch(AuthenticationException $e) {
			throw new InvalidRequestException(LANG('old_password_is_not_correct'));
		}

		$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
		$this->db->updateSystemUser(
			$this->su->id, $this->su->uid, $this->su->username, $this->su->display_name, $newPasswordHash,
			$this->su->ldap, $this->su->email, $this->su->phone, $this->su->mobile, $this->su->description, $this->su->locked, $this->su->system_user_role_id
		);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $this->su->id, 'oco.system_user.update_password', []);
	}
	public function editSystemUser($id, $username, $display_name, $description, $password, $systemUserRoleId) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$u = $this->db->selectSystemUser($id);
		if($u === null) throw new NotFoundException();
		if(!empty($u->ldap)) {
			$checkDescription = $u->description;
			if($checkDescription === null) $checkDescription = '';
			if($u->username !== $username
			|| $u->display_name !== $display_name
			|| $checkDescription !== $description
			|| $u->system_user_role_id !== $systemUserRoleId
			|| !empty($password)) {
				throw new InvalidRequestException(LANG('ldap_accounts_cannot_be_modified'));
			}
		}

		if(empty(trim($username))) {
			throw new InvalidRequestException(LANG('username_cannot_be_empty'));
		}
		$checkUser = $this->db->selectSystemUserByUsername($username);
		if($checkUser !== null && $checkUser->id !== $u->id) {
			throw new InvalidRequestException(LANG('username_already_exists'));
		}
		if(empty(trim($display_name))) {
			throw new InvalidRequestException(LANG('username_cannot_be_empty'));
		}
		$newPassword = $u->password;
		if(!empty($password)) {
			if(empty(trim($password))) {
				throw new InvalidRequestException(LANG('password_cannot_be_empty'));
			}
			$newPassword = password_hash($password, PASSWORD_DEFAULT);
		}

		$this->db->updateSystemUser(
			$u->id, $u->uid, trim($username), $display_name, $newPassword,
			$u->ldap, $u->email, $u->phone, $u->mobile, $description, $u->locked, $systemUserRoleId
		);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $u->id, 'oco.system_user.update', [
			'username'=>$username,
			'display_name'=>$display_name,
			'description'=>$description,
			'system_user_role_id'=>$systemUserRoleId
		]);
	}
	public function editSystemUserLocked($id, $locked) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$u = $this->db->selectSystemUser($id);
		if($u === null) throw new NotFoundException();

		$this->db->updateSystemUser(
			$u->id, $u->uid, $u->username, $u->display_name, $u->password,
			$u->ldap, $u->email, $u->phone, $u->mobile, $u->description, $locked, $u->system_user_role_id
		);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $u->id, 'oco.system_user.lock', ['locked'=>$locked]);
	}
	public function removeSystemUser($id) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);

		$u = $this->db->selectSystemUser($id);
		if($u === null) throw new NotFoundException();

		$result = $this->db->deleteSystemUser($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $u->id, 'oco.system_user.delete', []);
		return $result;
	}

	public function createEventQueryRule($log, $query) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_EVENT_QUERY_RULES);

		if(empty(trim($log)) || empty(trim($query))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}

		$insertId = $this->db->insertEventQueryRule($log, $query);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.event_query_rule.create', [
			'log'=>$log,
			'query'=>$query,
		]);
		return $insertId;
	}
	public function editEventQueryRule($id, $log, $query) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_EVENT_QUERY_RULES);

		if(empty(trim($log)) || empty(trim($query))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}

		$this->db->updateEventQueryRule($id, $log, $query);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $id, 'oco.event_query_rule.update', [
			'log'=>$log,
			'query'=>$query,
		]);
	}
	public function removeEventQueryRule($id) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_EVENT_QUERY_RULES);

		$u = $this->db->selectEventQueryRule($id);
		if($u === null) throw new NotFoundException();

		$result = $this->db->deleteEventQueryRule($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $u->id, 'oco.event_query_rule.delete', []);
		return $result;
	}

	public function editLicense($license) {
		$this->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);

		$insertId = $this->db->insertOrUpdateSettingByKey('license', $license);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.setting.update', [
			'license'=>$license,
		]);
		return $insertId;
	}

}
