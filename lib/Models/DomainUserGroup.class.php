<?php

namespace Models;

class DomainUserGroup extends HierarchicalGroup {

	const GET_OBJECT_FUNCTION = 'selectDomainUserGroup';
	const GET_OBJECTS_FUNCTION = 'getDomainUserGroups';

	public $id;
	public $parent_domain_user_group_id;
	public $name;


	public function __construct($db=null) {
		parent::__construct($db);
	}

	public function getParentId() {
		return $this->parent_domain_user_group_id;
	}

}
