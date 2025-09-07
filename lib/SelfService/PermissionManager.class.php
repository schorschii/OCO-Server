<?php

namespace SelfService;

class PermissionManager extends \PermissionManager {

	function __construct(\DatabaseController $db, \Models\DomainUser $user) {
		// with this constructor, we ensure that $user is typeof DomainUser
		parent::__construct($db, $user);
	}

	public function hasPermission($ressource, String $method, $ressourceParentGroups=null): bool {
		// check special permissions defined in array root if no object was given
		if(empty($ressource)) {
			if(!isset($this->permData[$method])) return false;
			return ((bool) $this->permData[$method]);
		}

		// check specific ressource type permissions
		if($ressource instanceof \Models\Computer) {
			$groups = $this->db->selectAllComputerGroupByComputerId($ressource->id);
			$parentGroups = [];
			foreach($groups as $group) {
				$parentGroups = array_merge($parentGroups, $this->getParentGroupsRecursively($group));
			}
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new \Models\ComputerGroup()), $parentGroups, $ressource, $method
			);

		} else if($ressource instanceof \Models\Package) {
			// check permission in context of package groups
			$groups = $this->db->selectAllPackageGroupByPackageId($ressource->id);
			$parentGroups = [];
			foreach($groups as $group) {
				$parentGroups = array_merge($parentGroups, $this->getParentGroupsRecursively($group));
			}
			if($this->checkRessourcePermission(
				get_class($ressource), get_class(new \Models\PackageGroup()), $parentGroups, $ressource, $method
			)) return true;

			// check permission in context of package family
			$family = \Models\PackageFamily::__constructWithId($ressource->package_family_id);
			return $this->checkRessourcePermission(
				get_class($ressource), get_class(new \Models\PackageFamily()), [$family], $ressource, $method
			);

		} else {
			$ressourceGroupType = null;
			if(!empty($ressourceParentGroups) && is_array($ressourceParentGroups)) $ressourceGroupType = get_class($ressourceParentGroups[0]);
			return $this->checkRessourcePermission(
				get_class($ressource), $ressourceGroupType, $ressourceParentGroups, $ressource, $method
			);
		}
	}

	// self service computer permissions can be granted by time frame, which means that the
	// domain user has only access if his last login on this computer was less than the defined value in seconds ago
	private function timeCheck(Object $ressource, $value): bool {
		if($ressource instanceof \Models\Computer) {
			if(is_bool($value)) {
				return $value;
			} else {
				$lastLogon = $this->db->selectLastDomainUserLogonByDomainUserIdAndComputerId($this->user->id, $ressource->id);
				if(!$lastLogon) return false;
				$lastLogonUnixTime = strtotime($lastLogon->timestamp);
				return (time() - $lastLogonUnixTime < intval($value));
			}
		} else {
			return ((bool) $value);
		}
	}

	protected function checkRessourcePermission(String $ressourceType, String $ressourceGroupType=null, Array $assignedGroups=null, Object $ressource, String $method): bool {
		if(isset($this->permData[$ressourceType])) {
			// 1st try: check permissions defined in array root if no specific object was given (e.g. create permissions)
			if(empty($ressource->id)) {
				if(!isset($this->permData[$ressourceType][$method])) return false;
				return ((bool) $this->permData[$ressourceType][$method]);
			}

			// 2nd try: check if specific ressource ID is defined in access list
			foreach($this->permData[$ressourceType] as $key => $item) {
				if($key === intval($ressource->id) && isset($item[$method]))
					return $this->timeCheck($ressource, $item[$method]);
			}

			// 3rd try: check if `own` rules are applicable (currently only implemented for job containers)
			if(isset($this->permData[$ressourceType]['own'][$method])
			&& property_exists($ressource, 'created_by_domain_user_id')
			&& $ressource->created_by_domain_user_id === $this->user->id)
				return ((bool) $this->permData[$ressourceType]['own'][$method]);

			// 4th try: check general permissions for this ressource type
			if(isset($this->permData[$ressourceType]['*'][$method]))
				return $this->timeCheck($ressource, $this->permData[$ressourceType]['*'][$method]);
		}

		// 5th try: check inherited group permissions
		if(!empty($ressourceGroupType)
		&& isset($this->permData[$ressourceGroupType])
		&& !empty($assignedGroups)) {
			foreach($assignedGroups as $group) {
				foreach($this->permData[$ressourceGroupType] as $key => $item) {
					if($key !== intval($group->id)) continue;

					if($ressource instanceof \Models\ComputerGroup || $ressource instanceof \Models\PackageGroup || $ressource instanceof \Models\ReportGroup) {
						// if we are checking the permission of a group object, read from the permission method directly inside the $item
						if(isset($item[$method])) {
							return ((bool) $item[$method]);
						}
					} else {
						// otherwise, read from the permission method in the 'items' dict
						if(isset($item['items'][$method])) {
							return $this->timeCheck($ressource, $item['items'][$method]);
						}
					}
				}
			}
		}

		// otherwise: access denied
		return false;
	}

}
