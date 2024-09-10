<?php

namespace Models;

class MobileDeviceGroup implements IHierarchicalGroup {

	public $id;
	public $parent_mobile_device_group_id;
	public $name;

	public function getParentId() {
		return $this->parent_mobile_device_group_id;
	}

}
