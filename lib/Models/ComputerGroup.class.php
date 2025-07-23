<?php

namespace Models;

class ComputerGroup extends HierarchicalGroup {

	const GET_OBJECT_FUNCTION = 'selectComputerGroup';
	const GET_OBJECTS_FUNCTION = 'getComputerGroups';

	public $id;
	public $parent_computer_group_id;
	public $name;


	public function __construct($db=null) {
		parent::__construct($db);
	}

	public function getParentId() {
		return $this->parent_computer_group_id;
	}

}
