<?php

namespace Models;

abstract class Job {

	// generic job attributes
	public $id;
	public $computer_id;
	public $package_id;
	public $procedure;
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

	// constants
	public const STATE_WAITING_FOR_AGENT = 0;
	public const STATE_FAILED = -1;
	public const STATE_EXPIRED = -2;
	public const STATE_OS_INCOMPATIBLE = -3;
	public const STATE_PACKAGE_CONFLICT = -4;
	public const STATE_ALREADY_INSTALLED = -5;
	public const STATE_DOWNLOAD_STARTED = 1;
	public const STATE_EXECUTION_STARTED = 2;
	public const STATE_SUCCEEDED = 3;

	// functions
	public function getIdForAgent() {
		if($this instanceof StaticJob) {
			return $this->id;
		} elseif($this instanceof DynamicJob) {
			return DynamicJob::PREFIX_DYNAMIC_ID.$this->id;
		}
	}
	public function isEnabled() {
		if($this instanceof StaticJob) {
			return $this->job_container_enabled;
		} elseif($this instanceof DynamicJob) {
			return $this->deployment_rule_enabled;
		}
	}
	public function getPriority() {
		if($this instanceof StaticJob) {
			return $this->job_container_priority;
		} elseif($this instanceof DynamicJob) {
			return $this->deployment_rule_priority;
		}
	}
	public function getContainerId() {
		if($this instanceof StaticJob) {
			return $this->job_container_id;
		} elseif($this instanceof DynamicJob) {
			return 'deployment_rule::'.$this->deployment_rule_id;
		}
	}
	public function getSequenceMode() {
		if($this instanceof StaticJob) {
			return $this->job_container_sequence_mode;
		} elseif($this instanceof DynamicJob) {
			return $this->deployment_rule_sequence_mode;
		}
	}
	public function getAuthor() {
		if($this instanceof StaticJob) {
			return $this->job_container_author;
		} elseif($this instanceof DynamicJob) {
			return $this->deployment_rule_author;
		}
	}
	public function getIcon() {
		if($this->state == self::STATE_WAITING_FOR_AGENT) {
			if($this instanceof StaticJob) {
				$startTimeParsed = strtotime($this->job_container_start_time);
				if($startTimeParsed !== false && $startTimeParsed > time()) return 'img/schedule.dyn.svg';
			}
			return 'img/wait.dyn.svg';
		}
		if($this->state == self::STATE_DOWNLOAD_STARTED) return 'img/downloading.dyn.svg';
		if($this->state == self::STATE_EXECUTION_STARTED) return 'img/pending.dyn.svg';
		if($this->state == self::STATE_FAILED) return 'img/error.dyn.svg';
		if($this->state == self::STATE_EXPIRED) return 'img/timeout.dyn.svg';
		if($this->state == self::STATE_OS_INCOMPATIBLE) return 'img/error.dyn.svg';
		if($this->state == self::STATE_PACKAGE_CONFLICT) return 'img/error.dyn.svg';
		if($this->state == self::STATE_SUCCEEDED) return 'img/success.dyn.svg';
		if($this->state == self::STATE_ALREADY_INSTALLED) return 'img/success.opacity.svg';
		return 'img/warning.dyn.svg';
	}
	public function getStateString() {
		$returnCodeString = '';
		if($this->return_code != null) {
			$returnCodeString = ' ('.htmlspecialchars($this->return_code).')';
		}
		if($this->state == self::STATE_WAITING_FOR_AGENT) {
			if($this instanceof StaticJob) {
				$startTimeParsed = strtotime($this->job_container_start_time);
				if($startTimeParsed !== false && $startTimeParsed > time()) return LANG('waiting_for_start');
			}
			return LANG('waiting_for_agent');
		}
		elseif($this->state == self::STATE_FAILED)
			return LANG('failed').$returnCodeString;
		elseif($this->state == self::STATE_EXPIRED)
			return LANG('expired');
		elseif($this->state == self::STATE_OS_INCOMPATIBLE)
			return LANG('incompatible');
		elseif($this->state == self::STATE_PACKAGE_CONFLICT)
			return LANG('package_conflict');
		elseif($this->state == self::STATE_ALREADY_INSTALLED)
			return LANG('already_installed');
		elseif($this->state == self::STATE_DOWNLOAD_STARTED)
			return LANG('download_started');
		elseif($this->state == self::STATE_EXECUTION_STARTED)
			return LANG('execution_started');
		elseif($this->state == self::STATE_SUCCEEDED)
			return LANG('succeeded').$returnCodeString;
		else return $this->state;
	}

	public static function sortJobs($a, $b) {
		$prioA = $a->getPriority();
		$prioB = $b->getPriority();
		if($prioA === $prioB) {
			return $a->sequence <=> $b->sequence;
		}
		return $prioB <=> $prioA;
	}

}
