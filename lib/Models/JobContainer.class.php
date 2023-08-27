<?php

namespace Models;

class JobContainer {

	public $id;
	public $name;
	public $enabled;
	public $start_time;
	public $end_time;
	public $notes;
	public $wol_sent;
	public $shutdown_waked_after_completion;
	public $sequence_mode;
	public $priority;
	public $agent_ip_ranges;
	public $time_frames;
	public $self_service;
	public $created;
	public $created_by_system_user_id;
	public $created_by_domain_user_id;

	// joined values
	public $created_by_system_user_username;
	public $created_by_domain_user_username;

	// aggregated values
	public $execution_finished;

	// constants (= icon names)
	public const STATUS_SUCCEEDED = 'success';
	public const STATUS_FAILED = 'error';
	public const STATUS_IN_PROGRESS = 'wait';
	public const STATUS_WAITING_FOR_START = 'schedule';
	public const SEQUENCE_MODE_IGNORE_FAILED = 0;
	public const SEQUENCE_MODE_ABORT_AFTER_FAILED = 1;
	public const RETURN_CODE_AGENT_ERROR = -9999;
	public const RETURN_CODE_ABORT_AFTER_FAILED = -8888;

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
			$startTimeParsed = strtotime($this->start_time);
			if($startTimeParsed !== false && $startTimeParsed > time())
				return JobContainer::STATUS_WAITING_FOR_START;
			else return JobContainer::STATUS_IN_PROGRESS;
		}
		elseif($errors > 0) return JobContainer::STATUS_FAILED;
		else return JobContainer::STATUS_SUCCEEDED;
	}

}
