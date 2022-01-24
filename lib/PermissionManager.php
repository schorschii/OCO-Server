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

	const RESSOURCE_TYPE_COMPUTER       = 'computer_management';
	const RESSOURCE_TYPE_COMPUTER_GROUP = 'computer_group_management';
	const RESSOURCE_TYPE_PACKAGE        = 'package_management';
	const RESSOURCE_TYPE_PACKAGE_FAMILY = 'package_family_management';
	const RESSOURCE_TYPE_PACKAGE_GROUP  = 'package_group_management';
	const RESSOURCE_TYPE_JOB_CONTAINER  = 'job_container_management';
	const RESSOURCE_TYPE_REPORT         = 'report_management';
	const RESSOURCE_TYPE_REPORT_GROUP   = 'report_group_management';
	const RESSOURCE_TYPE_DOMAIN_USER    = 'domain_user_management';

	const SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT = 'system_user_management';
	const SPECIAL_PERMISSION_SOFTWARE_VIEW          = 'recognised_software_view';
	const SPECIAL_PERMISSION_CLIENT_API             = 'client_api_allowed';
	const SPECIAL_PERMISSION_CLIENT_WEB_FRONTEND    = 'client_web_frontend_allowed';

	private /*DatabaseController*/ $db;
	private /*SystemUser*/ $systemUser;
	private /*Array*/ $permData;

	function __construct(DatabaseController $db, SystemUser $systemUser) {
		if(empty($systemUser->system_user_role_permissions)) {
			throw new Exception('No permission definition data found for this system user!');
		}
		$this->db = $db;
		$this->systemUser = $systemUser;
		$this->permData = json_decode($systemUser->system_user_role_permissions, true);
	}

	public function hasPermission($ressource, String $method): bool {
		// check special permissions defined in array root if no object was given
		if(empty($ressource)) {
			if(!isset($this->permData[$method])) return false;
			return ((bool) $this->permData[$method]);
		}

		// check specific ressource type permissions
		if($ressource instanceof Computer) {
			$groups = $this->db->getGroupByComputer($ressource->id);
			$parentGroups = [];
			foreach($groups as $group) {
				$parentGroups = array_merge($parentGroups, $this->getParentGroupsRecursively($group));
			}
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_COMPUTER, self::RESSOURCE_TYPE_COMPUTER_GROUP, $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof ComputerGroup) {
			$parentGroups = $this->getParentGroupsRecursively($ressource);
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_COMPUTER_GROUP, self::RESSOURCE_TYPE_COMPUTER_GROUP, $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof Package) {
			// check permission in context of package groups
			$groups = $this->db->getGroupByPackage($ressource->id);
			$parentGroups = [];
			foreach($groups as $group) {
				$parentGroups = array_merge($parentGroups, $this->getParentGroupsRecursively($group));
			}
			if($this->checkRessourcePermission(
				self::RESSOURCE_TYPE_PACKAGE, self::RESSOURCE_TYPE_PACKAGE_GROUP, $parentGroups, $ressource, $method
			)) return true;

			// check permission in context of package family
			$family = PackageFamily::__constructWithId($ressource->package_family_id);
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_PACKAGE, self::RESSOURCE_TYPE_PACKAGE_FAMILY, [$family], $ressource, $method
			);

		} else if($ressource instanceof PackageFamily) {
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_PACKAGE_FAMILY, null, null, $ressource, $method
			);

		} else if($ressource instanceof PackageGroup) {
			$parentGroups = $this->getParentGroupsRecursively($ressource);
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_PACKAGE_GROUP, self::RESSOURCE_TYPE_PACKAGE_GROUP, $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof JobContainer) {
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_JOB_CONTAINER, null, null, $ressource, $method
			);

		} else if($ressource instanceof Report) {
			$parentGroups = [];
			if($ressource->report_group_id != null) {
				$group = $this->db->getReportGroup($ressource->report_group_id);
				$parentGroups = $this->getParentGroupsRecursively($group);
			}
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_REPORT, self::RESSOURCE_TYPE_REPORT_GROUP, $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof ReportGroup) {
			$parentGroups = $this->getParentGroupsRecursively($ressource);
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_REPORT_GROUP, self::RESSOURCE_TYPE_REPORT_GROUP, $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof DomainUser) {
			return $this->checkRessourcePermission(
				self::RESSOURCE_TYPE_DOMAIN_USER, null, null, new DomainUser() /*no specific check*/, $method
			);

		} else {
			throw new InvalidArgumentException('Permission check for this ressource type is not implemented');
		}
	}

	// as defined, all parent group access privileges also apply to sub groups
	// so we query all parent groups to also check the privileges of them
	private function getParentGroupsRecursively(Object $groupRessource) {
		$parentGroups = [$groupRessource];
		if($groupRessource instanceof ComputerGroup) {
			while($groupRessource->parent_computer_group_id != null) {
				$parentGroup = $this->db->getComputerGroup($groupRessource->parent_computer_group_id);
				$parentGroups[] = $parentGroup;
				$groupRessource = $parentGroup;
			}

		} else if($groupRessource instanceof PackageGroup) {
			while($groupRessource->parent_package_group_id != null) {
				$parentGroup = $this->db->getPackageGroup($groupRessource->parent_package_group_id);
				$parentGroups[] = $parentGroup;
				$groupRessource = $parentGroup;
			}

		} else if($groupRessource instanceof ReportGroup) {
			while($groupRessource->parent_report_group_id != null) {
				$parentGroup = $this->db->getReportGroup($groupRessource->parent_report_group_id);
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
				if($key === intval($ressource->id) && isset($item[$method]))
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

					if($ressource instanceof ComputerGroup || $ressource instanceof PackageGroup || $ressource instanceof ReportGroup) {
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

class PermissionException extends Exception {}
