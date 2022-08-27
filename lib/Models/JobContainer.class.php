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
	public $created;

	// aggregated values
	public $execution_finished;

	// constants (= icon names)
	public const STATUS_SUCCEEDED = 'tick';
	public const STATUS_FAILED = 'error';
	public const STATUS_IN_PROGRESS = 'wait';
	public const STATUS_WAITING_FOR_START = 'schedule';
	public const SEQUENCE_MODE_IGNORE_FAILED = 0;
	public const SEQUENCE_MODE_ABORT_AFTER_FAILED = 1;
	public const RETURN_CODE_ABORT_AFTER_FAILED = -8888;

}
