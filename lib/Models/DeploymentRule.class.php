<?php

namespace Models;

class DeploymentRule {

	public $id;
	public $name;
	public $notes;
	public $enabled;
	public $computer_group_id;
	public $package_group_id;
	public $sequence_mode;
	public $priority;
	public $auto_uninstall;
	public $post_action_timeout;
	public $created;
	public $created_by_system_user_id;

	// joined user attributes
	public $installed_by_system_user_username;

	// constants (= icon names)
	public const STATUS_SUCCEEDED = 'success';
	public const STATUS_FAILED = 'error';
	public const STATUS_IN_PROGRESS = 'wait';
	public const STATUS_WAITING_FOR_START = 'schedule';

	// functions
	public function getStatus($jobs) {
		$waitings = 0;
		$errors = 0;
		foreach($jobs as $job) {
			if($job->state == Job::STATE_WAITING_FOR_AGENT
			|| $job->state == Job::STATE_DOWNLOAD_STARTED
			|| $job->state == Job::STATE_EXECUTION_STARTED) {
				$waitings ++;
			}
			if($job->state == Job::STATE_FAILED
			|| $job->state == Job::STATE_EXPIRED
			|| $job->state == Job::STATE_OS_INCOMPATIBLE
			|| $job->state == Job::STATE_PACKAGE_CONFLICT) {
				$errors ++;
			}
		}
		if($waitings > 0) {
			return DeploymentRule::STATUS_IN_PROGRESS;
		}
		elseif($errors > 0) return DeploymentRule::STATUS_FAILED;
		else return DeploymentRule::STATUS_SUCCEEDED;
	}

}
