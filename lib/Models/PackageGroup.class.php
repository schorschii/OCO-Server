<?php

namespace Models;

class PackageGroup extends HierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = 'selectPackageGroup';

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
