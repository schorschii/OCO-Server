<?php

class RecursivePolicyCompiler {

	const MANIFESTATION_LINUX   = 'manifestation_linux';
	const MANIFESTATION_MACOS   = 'manifestation_macos';
	const MANIFESTATION_WINDOWS = 'manifestation_windows';

	private $db;

	function __construct(\DatabaseController $db) {
		$this->db = $db;
	}

	function getManifestationTypeByComputer(Models\Computer $computer) {
		$manifestationType = null;
		if($computer->getOsType() == Models\Computer::OS_TYPE_LINUX)
			$manifestationType = self::MANIFESTATION_LINUX;
		elseif($computer->getOsType() == Models\Computer::OS_TYPE_MACOS)
			$manifestationType = self::MANIFESTATION_MACOS;
		elseif($computer->getOsType() == Models\Computer::OS_TYPE_WINDOWS)
			$manifestationType = self::MANIFESTATION_WINDOWS;
		return $manifestationType;
	}

	function getPoliciesForComputer(Models\Computer $computer, bool $manifestation) {
		$manifestationType = $this->getManifestationTypeByComputer($computer);
		if(!$manifestationType) return [];
		$policies = [];
		// process default domain policy
		foreach($this->db->selectAllPolicyObjectItemByComputerGroup(null) as $policy) {
			if(empty($policy->$manifestationType)) $policy->incompatible = true;
			$policies[':'.$policy->id] = $policy;
		}
		// process computer group assigned policies
		foreach($this->db->selectAllComputerGroupByComputerId($computer->id) as $cg) {
			$policies = array_merge(
				$policies,
				$this->getPoliciesForGroup($cg, $manifestationType)
			);
		}
		if($manifestation)
			return $this->compileManifestation($policies, $manifestationType);
		return $policies;
	}

	function getPoliciesForDomainUserOnComputer(Models\DomainUser $du, Models\Computer $computer, bool $manifestation) {
		$manifestationType = $this->getManifestationTypeByComputer($computer);
		if(!$manifestationType) return [];
		$policies = [];
		// process default domain policy
		foreach($this->db->selectAllPolicyObjectItemByDomainUserGroup(null) as $policy) {
			if(empty($policy->$manifestationType)) $policy->incompatible = true;
			$policies[':'.$policy->id] = $policy;
		}
		// process user group assigned policies
		foreach($this->db->selectAllDomainUserGroupByDomainUserId($du->id) as $dug) {
			$policies = array_merge(
				$policies,
				$this->getPoliciesForGroup($dug, $manifestationType)
			);
		}
		if($manifestation)
			return $this->compileManifestation($policies, $manifestationType);
		return $policies;
	}

	private function getPoliciesForGroup(Models\HierarchicalGroup $group, string $manifestationType) {
		$policies = [];
		$currentGroup = $group;
		$currentGroupId = $group->getId();
		while($currentGroupId) {
			$currentGroup = call_user_func([$this->db, $currentGroup::GET_OBJECT_FUNCTION], $currentGroupId);
			if(!$currentGroup instanceof Models\IHierarchicalGroup)
				throw new \Exception('Group object does not conform to IHierarchicalGroup');

			if($group instanceof Models\ComputerGroup)
				$items = $this->db->selectAllPolicyObjectItemByComputerGroup($currentGroupId);
			elseif($group instanceof Models\DomainUserGroup)
				$items = $this->db->selectAllPolicyObjectItemByDomainUserGroup($currentGroupId);
			foreach($items as $policy) {
				// check compatibility
				if(empty($policy->$manifestationType))
					$policy->incompatible = true;
				// ensure that array key is a string for array_merge logic!
				$key = ':'.$policy->id;
				// do not override policy with policy from a parent group!
				if(!isset($policies[$key]))
					$policies[$key] = $policy;
			}

			$currentGroupId = $currentGroup->getParentId();
		}
		return $policies;
	}

	private function compileManifestation(array $policies, string $manifestationType) {
		$manifestations = [];
		foreach($policies as $policy) {
			foreach(explode("\n", $policy->$manifestationType) as $manifestation) {
				if(empty($manifestation)) continue;
				// determine data type
				// return as list/dict
				if($policy->options == 'LIST' || $policy->options == 'DICT')
					$manifestations[$manifestation] = json_decode($policy->value, true);
				// return as int
				else if(is_numeric($policy->value)
				&& (substr($policy->options, 0, 3) == 'INT' || in_array($policy->value, json_decode($policy->options, true) ?? [])))
					$manifestations[$manifestation] = intval($policy->value);
				// return as bool
				else if(($policy->value === 'true' || $policy->value === 'false')
				&& in_array($policy->value==='true', json_decode($policy->options, true) ?? [], true))
					$manifestations[$manifestation] = $policy->value==='true';
				// return as string
				else
					$manifestations[$manifestation] = strval($policy->value);
			}
		}
		return $manifestations;
	}

}
