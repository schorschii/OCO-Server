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
		else
			throw new \RuntimeException('Unknown OS type!');
		return $manifestationType;
	}

	function getPoliciesForComputer(Models\Computer $computer) {
		$manifestationType = $this->getManifestationTypeByComputer($computer);
		$policies = [];

		// process default domain policy
		foreach($this->db->selectAllPolicyObjectItemByComputerGroup(null) as $policy) {
			if(empty($policy->$manifestationType)) continue;
			$policies = $this->compileManifestation($policies, $policy->$manifestationType, $policy->options, $policy->value);
		}

		// process computer group assigned policies
		foreach($this->db->selectAllComputerGroupByComputerId($computer->id) as $cg) {
			$policies = array_merge(
				$policies,
				$this->getPoliciesForGroup($cg, $manifestationType)
			);
		}
		return $policies;
	}

	function getPoliciesForDomainUserOnComputer(Models\DomainUser $du, Models\Computer $computer) {
		$manifestationType = $this->getManifestationTypeByComputer($computer);
		$policies = [];

		// process default domain policy
		foreach($this->db->selectAllPolicyObjectItemByDomainUserGroup(null) as $policy) {
			if(empty($policy->$manifestationType)) continue;
			$policies = $this->compileManifestation($policies, $policy->$manifestationType, $policy->options, $policy->value);
		}

		// process user group assigned policies
		foreach($this->db->selectAllDomainUserGroupByDomainUserId($du->id) as $dug) {
			$policies = array_merge(
				$policies,
				$this->getPoliciesForGroup($dug, $manifestationType)
			);
		}
		return $policies;
	}

	function getPoliciesForGroup(Models\HierarchicalGroup $group, string $manifestationType) {
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
				if(empty($policy->$manifestationType)) continue;
				$policies = $this->compileManifestation($policies, $policy->$manifestationType, $policy->options, $policy->value);
			}

			$currentGroupId = $currentGroup->getParentId();
		}
		return $policies;
	}

	private function compileManifestation($existingPolicies, $newManifestation, $newOptions, $newValue) {
		$policies = [];
		foreach(explode("\n", $newManifestation) as $manifestation) {
			// do not override policy with policy from a parent group!
			if(array_key_exists($manifestation, $policies)) continue;
			// determine data type (int vs. string)
			if(is_numeric($newValue)
			&& (substr($newOptions, 0, 3) == 'INT' || in_array($newValue, json_decode($newOptions, true) ?? [])))
				$policies[$manifestation] = intval($newValue);
			else
				$policies[$manifestation] = strval($newValue);
		}
		return $policies;
	}

}
