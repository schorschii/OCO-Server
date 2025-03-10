<?php

namespace Models;

class ComputerGroup extends HierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = 'selectComputerGroup';

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
