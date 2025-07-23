<?php

namespace Models;

class PackageGroup extends HierarchicalGroup {

	const GET_OBJECT_FUNCTION = 'selectPackageGroup';
	const GET_OBJECTS_FUNCTION = 'getPackageGroups';

	public $id;
	public $parent_package_group_id;
	public $name;


	public function __construct($db=null) {
		parent::__construct($db);
	}

	public function getParentId() {
		return $this->parent_package_group_id;
	}

}
