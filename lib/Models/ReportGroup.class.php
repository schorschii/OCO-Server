<?php

namespace Models;

class ReportGroup extends HierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = 'selectReportGroup';

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
