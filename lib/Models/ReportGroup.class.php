<?php

namespace Models;

class ReportGroup extends HierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = 'selectReportGroup';

	public $id;
	public $parent_report_group_id;
	public $name;

	public function getId() {
		return $this->id;
	}
	public function getParentId() {
		return $this->parent_report_group_id;
	}
	public function getName() {
		return $this->name;
	}

}
