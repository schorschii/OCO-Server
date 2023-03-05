<?php

namespace Models;

class DynamicJob extends Job {

	static function __constructWithValues(
		$deployment_rule_id,
		$deployment_rule_name,
		$deployment_rule_created_by_system_user_id,
		$deployment_rule_enabled,
		$deployment_rule_priority,
		$computer_id,
		$computer_hostname,
		$package_id,
		$package_version,
		$package_family_name,
		$procedure,
		$success_return_codes,
		$is_uninstall,
		$download,
		$post_action,
		$post_action_timeout,
		$sequence,
		$state = Job::STATE_WAITING_FOR_AGENT,
		$return_code = null,
		$message = ''
		) {
		$item = new DynamicJob();
		$item->id = null;
		$item->deployment_rule_id = $deployment_rule_id;
		$item->deployment_rule_name = $deployment_rule_name;
		$item->deployment_rule_created_by_system_user_id = $deployment_rule_created_by_system_user_id;
		$item->deployment_rule_enabled = $deployment_rule_enabled;
		$item->deployment_rule_priority = $deployment_rule_priority;
		$item->computer_id = $computer_id;
		$item->computer_hostname = $computer_hostname;
		$item->package_id = $package_id;
		$item->package_version = $package_version;
		$item->package_family_name = $package_family_name;
		$item->procedure = $procedure;
		$item->success_return_codes = $success_return_codes;
		$item->is_uninstall = $is_uninstall;
		$item->download = $download;
		$item->post_action = $post_action;
		$item->post_action_timeout = $post_action_timeout;
		$item->sequence = $sequence;
		$item->state = $state;
		$item->return_code = $return_code;
		$item->message = $message;
		return $item;
	}

	// attributes
	public $deployment_rule_id;

	// joined deployment rule attributes
	public $deployment_rule_name;
	public $deployment_rule_created_by_system_user_id;
	public $deployment_rule_enabled;
	public $deployment_rule_sequence_mode;
	public $deployment_rule_priority;

	// constants
	public const PREFIX_DYNAMIC_ID = 'dynamic-';

}
