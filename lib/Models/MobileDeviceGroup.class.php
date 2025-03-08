<?php

namespace Models;

class MobileDeviceGroup extends HierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = 'selectMobileDeviceGroup';

	public $id;
	public $parent_mobile_device_group_id;
	public $name;

	public function getId() {
		return $this->id;
	}
	public function getParentId() {
		return $this->parent_mobile_device_group_id;
	}
	public function getName() {
		return $this->name;
	}

}
