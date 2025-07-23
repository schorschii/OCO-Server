<?php

namespace Models;

class ReportGroup extends HierarchicalGroup {

	const GET_OBJECT_FUNCTION = 'selectReportGroup';
	const GET_OBJECTS_FUNCTION = 'getReportGroups';

	public $id;
	public $parent_report_group_id;
	public $name;


	public function __construct($db=null) {
		parent::__construct($db);
	}

	public function getParentId() {
		return $this->parent_report_group_id;
	}

}
