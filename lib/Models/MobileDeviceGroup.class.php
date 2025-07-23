<?php

namespace Models;

class MobileDeviceGroup extends HierarchicalGroup {

	const GET_OBJECT_FUNCTION = 'selectMobileDeviceGroup';
	const GET_OBJECTS_FUNCTION = 'getMobileDeviceGroups';

	public $id;
	public $parent_mobile_device_group_id;
	public $name;


	public function __construct($db=null) {
		parent::__construct($db);
	}

	public function getParentId() {
		return $this->parent_mobile_device_group_id;
	}

}
