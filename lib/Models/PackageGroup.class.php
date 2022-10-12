<?php

namespace Models;

class PackageGroup implements IHierarchicalGroup {

	public $id;
	public $parent_package_group_id;
	public $name;

	public function getParentId() {
		return $this->parent_package_group_id;
	}

}
