<?php

namespace Models;

class PolicyDefinition {

	const CLASS_MACHINE = 1;
	const CLASS_USER    = 2;
	const CLASS_BOTH    = 3;

	public $id;
	public $policy_definition_group_id;
	public $parent_policy_definition_id;
	public $name;
	public $display_name;
	public $description;
	public $class;
	public $options;
	public $manifestation_linux;
	public $manifestation_macos;
	public $manifestation_windows;

}
