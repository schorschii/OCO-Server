<?php

abstract class MobileDeviceCommandControllerBase {

	protected $db;

	private function getRessourceRecursiveByMobileDeviceId($mdId, $callable) {
		$resources = [];
		foreach($this->db->selectAllMobileDeviceGroupByMobileDeviceId($mdId) as $mdg) {
			$newResources = [];

			$currentGroup = $mdg;
			$currentGroupId = $mdg->getId();
			while($currentGroupId) {
				$currentGroup = call_user_func([$this->db, $currentGroup::GET_OBJECT_FUNCTION], $currentGroupId);
				if(!$currentGroup instanceof Models\IHierarchicalGroup)
					throw new \Exception('Group object does not conform to IHierarchicalGroup');

				foreach(call_user_func($callable, $currentGroupId) as $newRessource) {
					// ensure that array key is a string for array_merge logic!
					$key = ':'.$newRessource->id;
					// do not override ressources from parent groups!
					if(!isset($newResources[$key]))
						$newResources[$key] = $newRessource;
				}

				$currentGroupId = $currentGroup->getParentId();
			}

			$resources = array_merge($resources, $newResources);
		}
		return $resources;
	}
	function getManagedAppsByMobileDeviceId($mdId) {
		return $this->getRessourceRecursiveByMobileDeviceId($mdId, [$this->db, 'selectAllManagedAppByMobileDeviceGroupId']);
	}
	function getProfilesByMobileDeviceId($mdId) {
		return $this->getRessourceRecursiveByMobileDeviceId($mdId, [$this->db, 'selectAllProfileByMobileDeviceGroupId']);
	}

	static function replacePlaceholders(&$payload, array $parameters) {
		if(is_string($payload)) {
			preg_match_all('(\$\$[A-Za-z0-9]+\$\$)', $payload, $matches);
			foreach($matches[0] as $paramToReplace) {
				$found = false;
				foreach($parameters as $key => $value) {
					if($paramToReplace === '$$'.$key.'$$')
						$found = true;
				}
				if(!$found) // exit if a required param is not defined
					return false;
			}
			foreach($parameters as $key => $value) {
				$payload = str_replace('$$'.$key.'$$', $value, $payload);
			}
		} elseif(is_array($payload)) {
			foreach($payload as $key => $value) {
				if(!self::replacePlaceholders($value, $parameters))
					return false;
				$payload[$key] = $value;
			}
		}
		return true;
	}

}
