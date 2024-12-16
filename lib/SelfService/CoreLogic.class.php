<?php

namespace SelfService;

class CoreLogic {

	private /*\DatabaseController*/ $db;
	private /*\Models\DomainUser*/ $du;
	private /*PermissionManager*/ $pm;

	function __construct(\DatabaseController $db, \Models\DomainUser $domainUser=null) {
		$this->db = $db;
		$this->du = $domainUser;
	}

	/*** Permission Check Logic ***/
	public function checkPermission(Object $ressource, String $method, Bool $throw=true) {
		if($this->pm === null) $this->pm = new PermissionManager($this->db, $this->du);
		$checkResult = $this->pm->hasPermission($ressource, $method);
		if(!$checkResult && $throw) throw new \PermissionException();
		return $checkResult;
	}
	public function getPermissionEntry($ressource, String $method) {
		if($this->pm === null) $this->pm = new PermissionManager($this->db, $this->du);
		return $this->pm->getPermissionEntry($ressource, $method);
	}

	/*** Computer Operations ***/
	public function getMyComputers() {
		$computersFiltered = [];
		foreach($this->db->selectAllComputer() as $computer) {
			if($this->checkPermission($computer, PermissionManager::METHOD_READ, false))
				$computersFiltered[] = $computer;
		}
		return $computersFiltered;
	}
	public function getMyComputer($id) {
		$computer = $this->db->selectComputer($id);
		if(empty($computer)) throw new \NotFoundException();
		$this->checkPermission($computer, PermissionManager::METHOD_READ);
		return $computer;
	}
	public function wolMyComputers($computerIds, $debugOutput=true) {
		$wolMacAdresses = [];
		foreach($computerIds as $id) {
			$computer = $this->db->selectComputer($id);
			if(empty($computer)) throw new \NotFoundException();
			$this->checkPermission($computer, PermissionManager::METHOD_WOL);

			foreach($this->db->selectAllComputerNetworkByComputerId($computer->id) as $n) {
				if(empty($n->mac) || $n->mac == '-' || $n->mac == '?') continue;
				$wolMacAdresses[] = $n->mac;
			}
		}
		if(count($wolMacAdresses) == 0) {
			throw new \Exception(LANG('no_mac_addresses_for_wol'));
		}
		$this->db->insertLogEntry(\Models\Log::LEVEL_INFO, $this->du->username, null, 'oco.self_service.computer.wol', $wolMacAdresses);
		$wolController = new \WakeOnLan($this->db);
		$wolController->wol($wolMacAdresses, $debugOutput);
		return true;
	}

	/*** Package Operations ***/
	public function getMyPackages() {
		$packagesFiltered = [];
                $myComputers = $this->getMyComputers();
                foreach($this->db->selectAllPackage() as $package) {
                        foreach($myComputers as $computer){
                                if( $this->db->selectPackage($package->id)->compatible_os == $computer->os && $this->checkPermission($package, PermissionManager::METHOD_READ, false))
                                        $packagesFiltered[] = $this->db->selectPackage($package->id);
                        }
                }
                return $packagesFiltered;

	}
	public function getMyPackage($id) {
		$package = $this->db->selectPackage($id);
		if(empty($package)) throw new \NotFoundException();
		$this->checkPermission($package, PermissionManager::METHOD_READ);
		return $package;
	}

	/*** Job Operations ***/
	public function getMyJobContainers() {
		$jobContainersFiltered = [];
		foreach($this->db->selectAllJobContainer(true) as $jobContainer) {
			if($this->checkPermission($jobContainer, PermissionManager::METHOD_READ, false))
				$jobContainersFiltered[] = $jobContainer;
		}
		return $jobContainersFiltered;
	}
	public function getMyJobContainer($id) {
		$jobContainer = $this->db->selectJobContainer($id);
		if(empty($jobContainer)) throw new \NotFoundException();
		$this->checkPermission($jobContainer, PermissionManager::METHOD_READ);
		return $jobContainer;
	}
	public function deploySelfService($name, $computerIds, $packageIds, $dateStart, $dateEnd, $useWol, $shutdownWakedAfterCompletion, $restartTimeout, $forceInstallSameVersion, $sequenceMode) {
		// check permission to given computer and package ids
		$this->checkPermission(new \Models\JobContainer(), PermissionManager::METHOD_CREATE);
		foreach($computerIds as $cid) {
			$this->checkPermission($this->getMyComputer($cid), PermissionManager::METHOD_DEPLOY);
		}
		foreach($packageIds as $pid) {
			$this->checkPermission($this->getMyPackage($pid), PermissionManager::METHOD_DEPLOY);
		}

		// determine priority
		$priority = 0;
		$permissionEntry = $this->getPermissionEntry(new \Models\JobContainer(), PermissionManager::METHOD_CREATE);
		if(isset($permissionEntry['create_priority']) && is_int($permissionEntry['create_priority'])) {
			$priority = $permissionEntry['create_priority'];
		}

		// use the normal admin client CoreLogic for dependency resolving logic etc.
		$cl2 = new \CoreLogic($this->db, null, $this->du);
		$jcid = $cl2->deploy(
			$name, ''/*description*/,
			$computerIds, []/*computerGroupIds*/, []/*$computerReportIds*/,
			$packageIds, []/*$packageGroupIds*/, []/*$packageReportIds*/,
			$dateStart, $dateEnd,
			$useWol, $shutdownWakedAfterCompletion, $restartTimeout,
			$forceInstallSameVersion, $sequenceMode, $priority,
			[]/*constraintIpRanges*/, []/*constraintTimeFrames*/,
			1/*selfService*/
		);

		// add log entry and return insert id
		if($jcid) {
			$jobs = $this->db->selectAllStaticJobByJobContainer($jcid);
			$this->db->insertLogEntry(\Models\Log::LEVEL_INFO, $this->du->username, $jcid, 'oco.self_service.job_container.create', ['name'=>$name, 'jobs'=>$jobs]);
			return $jcid;
		}
	}
	public function uninstallSelfService($name, $installationIds, $dateStart, $dateEnd, $useWol, $shutdownWakedAfterCompletion, $restartTimeout, $sequenceMode) {
		// check permission to given computer and package ids
		$this->checkPermission(new \Models\JobContainer(), PermissionManager::METHOD_CREATE);
		foreach($installationIds as $id) {
			$ap = $this->db->selectComputerPackage($id); if(empty($ap)) continue;
			if(!$this->checkPermission($this->getMyComputer($ap->computer_id), PermissionManager::METHOD_DEPLOY, false)) continue;
			if(!$this->checkPermission($this->getMyPackage($ap->package_id), PermissionManager::METHOD_DEPLOY, false)) continue;
		}

		// determine priority
		$priority = 0;
		$permissionEntry = $this->getPermissionEntry(new \Models\JobContainer(), PermissionManager::METHOD_CREATE);
		if(isset($permissionEntry['create_priority']) && is_int($permissionEntry['create_priority'])) {
			$priority = $permissionEntry['create_priority'];
		}

		// use the normal admin client CoreLogic for dependency resolving logic etc.
		$cl2 = new \CoreLogic($this->db, null, $this->du);
		$jcid = $cl2->uninstall(
			$name, ''/*description*/,
			$installationIds,
			$dateStart, $dateEnd,
			$useWol, $shutdownWakedAfterCompletion, $restartTimeout,
			$sequenceMode, $priority, []/*constraintIpRanges*/,
			1/*selfService*/
		);

		// add log entry and return insert id
		if($jcid) {
			$jobs = $this->db->selectAllStaticJobByJobContainer($jcid);
			$this->db->insertLogEntry(\Models\Log::LEVEL_INFO, $this->du->username, $jcid, 'oco.self_service.job_container.create', ['name'=>$name, 'jobs'=>$jobs]);
			return $jcid;
		}
	}
	public function removeMyJobContainer($id) {
		$jc = $this->db->selectJobContainer($id);
		if(empty($jc)) throw new \NotFoundException();
		$this->checkPermission($jc, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteJobContainer($id);
		if(!$result) throw new \Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(\Models\Log::LEVEL_INFO, $this->du->username, $jc->id, 'oco.self_service.job_container.delete', json_encode($jc));
		return $result;
	}
	public function removeMyStaticJob($id) {
		$job = $this->db->selectStaticJob($id);
		if(empty($job) || !$job instanceof \Models\StaticJob) throw new \NotFoundException();
		$jc = $this->db->selectJobContainer($job->job_container_id);
		if(empty($jc)) throw new \NotFoundException();
		$this->checkPermission($jc, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteStaticJob($id);
		if(!$result) throw new \Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(\Models\Log::LEVEL_INFO, $this->du->username, $job->id, 'oco.self_service.job_container.job.delete', ['job_container_id'=>$jc->id]);
		return $result;
	}

}
