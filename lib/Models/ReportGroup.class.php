<?php

namespace Models;

class ReportGroup implements IHierarchicalGroup {

	public $id;
	public $parent_report_group_id;
	public $name;

	public function getParentId() {
		return $this->parent_report_group_id;
	}

}
