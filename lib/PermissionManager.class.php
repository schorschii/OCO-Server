<?php

class PermissionManager {

	/*
		 Class PermissionManager
		 Checks permissions of a given system user

		 "Darf er das?" ~ Chris Tall
	*/

	const METHOD_CREATE   = 'create';
	const METHOD_READ     = 'read';
	const METHOD_WOL      = 'wol';
	const METHOD_WRITE    = 'write';
	const METHOD_DEPLOY   = 'deploy';
	const METHOD_DOWNLOAD = 'download';
	const METHOD_DELETE   = 'delete';

	const SPECIAL_PERMISSION_DOMAIN_USER            = 'Models\\DomainUser';
	const SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT = 'Models\\SystemUser';
	const SPECIAL_PERMISSION_SOFTWARE_VIEW          = 'Models\\Software';
	const SPECIAL_PERMISSION_GENERAL_CONFIGURATION  = 'Special\\GeneralConfiguration';
	const SPECIAL_PERMISSION_EVENT_QUERY_RULES      = 'Special\\EventQueryRules';
	const SPECIAL_PERMISSION_VIEW_DELETED_OBJECTS   = 'Special\\DeletedObjects';
	const SPECIAL_PERMISSION_CLIENT_API             = 'Special\\ClientApi';
	const SPECIAL_PERMISSION_CLIENT_WEB_FRONTEND    = 'Special\\WebFrontend';

	private /*DatabaseController*/ $db;
	private /*Models\SystemUser*/ $systemUser;
	private /*Array*/ $permData;

	function __construct(DatabaseController $db, Models\SystemUser $systemUser) {
		$this->db = $db;
		$this->systemUser = $systemUser;
		$this->permData = json_decode($systemUser->system_user_role_permissions, true);
		if(empty($this->permData)) { // json_decode returns false on error; it is intentional that we also throw an error if the permission list is empty
			throw new Exception('Invalid or no permission definition data found for this system user!');
		}
	}

	public function getPermissionEntry($ressource, String $method=null) {
		if(is_object($ressource)) $ressource = get_class($ressource);
		if(!isset($this->permData[$ressource])) return false;
		return $this->permData[$ressource];
	}

	public function hasPermission($ressource, String $method, $ressourceParentGroups=null): bool {
		// check special permissions defined in array root if no object was given
		if(empty($ressource)) {
			if(!isset($this->permData[$method])) return false;
			return ((bool) $this->permData[$method]);
		}

		// check specific ressource type permissions
		if($ressource instanceof Models\Computer) {
			$groups = $this->db->selectAllComputerGroupByComputerId($ressource->id);
			$parentGroups = [];
			foreach($groups as $group) {
				$parentGroups = array_merge($parentGroups, $this->getParentGroupsRecursively($group));
			}
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new Models\ComputerGroup()), $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof Models\ComputerGroup) {
			$parentGroups = $this->getParentGroupsRecursively($ressource);
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new Models\ComputerGroup()), $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof Models\Package) {
			// check permission in context of package groups
			$groups = $this->db->selectAllPackageGroupByPackageId($ressource->id);
			$parentGroups = [];
			foreach($groups as $group) {
				$parentGroups = array_merge($parentGroups, $this->getParentGroupsRecursively($group));
			}
			if($this->checkRessourcePermission(
				get_class($ressource), get_class(new Models\PackageGroup()), $parentGroups, $ressource, $method
			)) return true;

			// check permission in context of package family
			$family = Models\PackageFamily::__constructWithId($ressource->package_family_id);
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new Models\PackageFamily()), [$family], $ressource, $method
			);

		} else if($ressource instanceof Models\PackageFamily) {
			return $this->checkRessourcePermission(
				get_class($ressource), null, null, $ressource, $method
			);

		} else if($ressource instanceof Models\PackageGroup) {
			$parentGroups = $this->getParentGroupsRecursively($ressource);
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new Models\PackageGroup()), $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof Models\JobContainer) {
			return $this->checkRessourcePermission(
				get_class($ressource), null, null, $ressource, $method
			);

		} else if($ressource instanceof Models\Report) {
			$parentGroups = [];
			if($ressource->report_group_id != null) {
				$group = $this->db->selectReportGroup($ressource->report_group_id);
				$parentGroups = $this->getParentGroupsRecursively($group);
			}
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new Models\ReportGroup()), $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof Models\ReportGroup) {
			$parentGroups = $this->getParentGroupsRecursively($ressource);
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new Models\ReportGroup()), $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof Models\DomainUser) {
			return $this->checkRessourcePermission(
				get_class($ressource), null, null, new Models\DomainUser() /*no specific check*/, $method
			);

		} else {
			$ressourceGroupType = null;
			if(!empty($ressourceParentGroups) && is_array($ressourceParentGroups)) $ressourceGroupType = get_class($ressourceParentGroups[0]);
			return $this->checkRessourcePermission(
				get_class($ressource), $ressourceGroupType, $ressourceParentGroups, $ressource, $method
			);
		}
	}

	// as defined, all parent group access privileges also apply to sub groups
	// so we query all parent groups to also check the privileges of them
	private function getParentGroupsRecursively(Object $groupRessource) {
		$parentGroups = [$groupRessource];
		if(!$groupRessource instanceof Models\IHierarchicalGroup) {
			throw new InvalidArgumentException('Permission check for this ressource type is not implemented');
		}
		if($groupRessource instanceof Models\ComputerGroup) {
			while($groupRessource->getParentId() != null) {
				$parentGroup = $this->db->selectComputerGroup($groupRessource->getParentId());
				$parentGroups[] = $parentGroup;
				$groupRessource = $parentGroup;
			}

		} elseif($groupRessource instanceof Models\PackageGroup) {
			while($groupRessource->getParentId() != null) {
				$parentGroup = $this->db->selectPackageGroup($groupRessource->getParentId());
				$parentGroups[] = $parentGroup;
				$groupRessource = $parentGroup;
			}

		} elseif($groupRessource instanceof Models\ReportGroup) {
			while($groupRessource->getParentId() != null) {
				$parentGroup = $this->db->selectReportGroup($groupRessource->getParentId());
				$parentGroups[] = $parentGroup;
				$groupRessource = $parentGroup;
			}

		} else {
			throw new InvalidArgumentException('Permission check for this ressource type is not implemented');
		}
		return $parentGroups;
	}

	private function checkRessourcePermission(String $ressourceType, String $ressourceGroupType=null, Array $assignedGroups=null, Object $ressource, String $method): bool {
		if(isset($this->permData[$ressourceType])) {
			// 1st try: check permissions defined in array root if no specific object was given (e.g. create permissions)
			if(empty($ressource->id)) {
				if(!isset($this->permData[$ressourceType][$method])) return false;
				return ((bool) $this->permData[$ressourceType][$method]);
			}

			// 2nd try: check if specific ressource ID is defined in access list
			foreach($this->permData[$ressourceType] as $key => $item) {
				if(strval($key) === strval($ressource->id) && isset($item[$method]))
					return ((bool) $item[$method]);
			}

			// 3rd try: check if `own` rules are applicable (currently only implemented for job containers)
			if(isset($this->permData[$ressourceType]['own'][$method])
			&& property_exists($ressource, 'author') && $ressource->author === $this->systemUser->username)
				return ((bool) $this->permData[$ressourceType]['own'][$method]);

			// 4th try: check general permissions for this ressource type
			if(isset($this->permData[$ressourceType]['*'][$method]))
				return ((bool) $this->permData[$ressourceType]['*'][$method]);
		}

		// 5th try: check inherited group permissions
		if(!empty($ressourceGroupType)
		&& isset($this->permData[$ressourceGroupType])
		&& !empty($assignedGroups)) {
			foreach($assignedGroups as $group) {
				foreach($this->permData[$ressourceGroupType] as $key => $item) {
					if($key !== intval($group->id)) continue;

					if($ressource instanceof Models\IHierarchicalGroup) {
						// if we are checking the permission of a group object, read from the permission method directly inside the $item
						if(isset($item[$method])) {
							return ((bool) $item[$method]);
						}
					} else {
						// otherwise, read from the permission method in the 'items' dict
						if(isset($item['items'][$method])) {
							return ((bool) $item['items'][$method]);
						}
					}
				}
			}
		}

		// otherwise: access denied
		return false;
	}

}
