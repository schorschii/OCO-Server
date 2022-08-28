<?php

namespace Models;

class Job {

	public $id;
	public $job_container_id;
	public $computer_id;
	public $package_id;
	public $package_procedure;
	public $success_return_codes;
	public $is_uninstall;
	public $download;
	public $post_action;
	public $post_action_timeout;
	public $sequence;
	public $state;
	public $return_code;
	public $message;
	public $wol_shutdown_set;
	public $download_started;
	public $execution_started;
	public $execution_finished;

	// joined computer attributes
	public $computer_hostname;

	// joined package attributes
	public $package_family_name;
	public $package_version;

	// joined job container attributes
	public $job_container_start_time = 0;
	public $job_container_author;

	// constants
	public const STATUS_WAITING_FOR_CLIENT = 0;
	public const STATUS_FAILED = -1;
	public const STATUS_EXPIRED = -2;
	public const STATUS_OS_INCOMPATIBLE = -3;
	public const STATUS_PACKAGE_CONFLICT = -4;
	public const STATUS_ALREADY_INSTALLED = -5;
	public const STATUS_DOWNLOAD_STARTED = 1;
	public const STATUS_EXECUTION_STARTED = 2;
	public const STATUS_SUCCEEDED = 3;

	// functions
	function getIcon() {
		if($this->state == self::STATUS_WAITING_FOR_CLIENT) {
			$startTimeParsed = strtotime($this->job_container_start_time);
			if($startTimeParsed !== false && $startTimeParsed > time()) return 'img/schedule.dyn.svg';
			else return 'img/wait.dyn.svg';
		}
		if($this->state == self::STATUS_DOWNLOAD_STARTED) return 'img/downloading.dyn.svg';
		if($this->state == self::STATUS_EXECUTION_STARTED) return 'img/pending.dyn.svg';
		if($this->state == self::STATUS_FAILED) return 'img/error.dyn.svg';
		if($this->state == self::STATUS_EXPIRED) return 'img/timeout.dyn.svg';
		if($this->state == self::STATUS_OS_INCOMPATIBLE) return 'img/error.dyn.svg';
		if($this->state == self::STATUS_PACKAGE_CONFLICT) return 'img/error.dyn.svg';
		if($this->state == self::STATUS_SUCCEEDED) return 'img/tick.dyn.svg';
		return 'img/warning.dyn.svg';
	}
	function getStateString() {
		$returnCodeString = '';
		if($this->return_code != null) {
			$returnCodeString = ' ('.htmlspecialchars($this->return_code).')';
		}
		if($this->state == self::STATUS_WAITING_FOR_CLIENT) {
			$startTimeParsed = strtotime($this->job_container_start_time);
			if($startTimeParsed !== false && $startTimeParsed > time()) return LANG('waiting_for_start');
			return LANG('waiting_for_agent');
		}
		elseif($this->state == self::STATUS_FAILED)
			return LANG('failed').$returnCodeString;
		elseif($this->state == self::STATUS_EXPIRED)
			return LANG('expired');
		elseif($this->state == self::STATUS_OS_INCOMPATIBLE)
			return LANG('incompatible');
		elseif($this->state == self::STATUS_PACKAGE_CONFLICT)
			return LANG('package_conflict');
		elseif($this->state == self::STATUS_ALREADY_INSTALLED)
			return LANG('already_installed');
		elseif($this->state == self::STATUS_DOWNLOAD_STARTED)
			return LANG('download_started');
		elseif($this->state == self::STATUS_EXECUTION_STARTED)
			return LANG('execution_started');
		elseif($this->state == self::STATUS_SUCCEEDED)
			return LANG('succeeded').$returnCodeString;
		else return $this->state;
	}

}
