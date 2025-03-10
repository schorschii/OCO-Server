<?php

namespace Models;

abstract class HierarchicalGroup implements IHierarchicalGroup {

	const GET_OBJECT_FUNCTION = null;

	private $db;

	public function __construct($db=null) {
		$this->db = $db;
	}

	public function getId() {
		return $this->id;
	}
	public function getName() {
		return $this->name;
	}

	public function getBreadcrumbString() {
		$currentGroupId = $this->getId();
		$groupStrings = [];
		while(true) {
			$currentGroup = call_user_func([$this->db, $this::GET_OBJECT_FUNCTION], $currentGroupId);
			if(!$currentGroup instanceof IHierarchicalGroup)
				throw new \Exception('Group object does not conform to IHierarchicalGroup');
			$groupStrings[] = $currentGroup->getName();
			if($currentGroup->getParentId() === null) {
				break;
			} else {
				$currentGroupId = $currentGroup->getParentId();
			}
		}
		$groupStrings = array_reverse($groupStrings);
		return implode(' » ', $groupStrings);
	}

}
