<?php

namespace Models;

class ComputerGroup implements IHierarchicalGroup {

	public $id;
	public $parent_computer_group_id;
	public $name;

	public function getParentId() {
		return $this->parent_computer_group_id;
	}

}
