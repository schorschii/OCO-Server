<?php

namespace Models;

class MobileDeviceCommand {

	public $id;
	public $mobile_device_id;
	public $parameter;
	public $state;
	public $message;
	public $finished;

	// constants
	const STATE_QUEUED  = 0;
	const STATE_SENT    = 2;
	const STATE_SUCCESS = 3;
	const STATE_FAILED  = -1;
	public const ICON_SUCCESS = 'success';
	public const ICON_FAILED = 'error';
	public const ICON_IN_PROGRESS = 'wait';

	// functions
	public function getStatus() {
		if($this->state == self::STATE_SUCCESS) return self::ICON_SUCCESS;
		elseif($this->state == self::STATE_FAILED) return self::ICON_FAILED;
		else return self::ICON_IN_PROGRESS;
	}

}
