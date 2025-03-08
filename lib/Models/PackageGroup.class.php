<?php

namespace Models;

class PackageGroup extends HierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = 'selectPackageGroup';

	public $id;
	public $parent_package_group_id;
	public $name;

	static function constructWithId($id) {
		$g = new self();
		$g->id = $id;
		return $g;
	}

	public function getId() {
		return $this->id;
	}
	public function getParentId() {
		return $this->parent_package_group_id;
	}
	public function getName() {
		return $this->name;
	}

}
