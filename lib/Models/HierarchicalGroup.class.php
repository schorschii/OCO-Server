<?php

namespace Models;

abstract class HierarchicalGroup implements IHierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = null;

	public function getBreadcrumbString($databaseController) {
		$currentGroupId = $this->getId();
		$groupStrings = [];
		while(true) {
			$currentGroup = call_user_func([$databaseController, $this::GET_OBJECT_FUNCTION], $currentGroupId);
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
