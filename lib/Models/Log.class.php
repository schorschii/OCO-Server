<?php

namespace Models;

class Log {

	public $id;
	public $level;
	public $user;
	public $object_id;
	public $action;
	public $data;

	// constants
	public const DEFAULT_VIEW_LIMIT = 80;

	public const LEVEL_DEBUG   = 0;
	public const LEVEL_INFO    = 1;
	public const LEVEL_WARNING = 2;
	public const LEVEL_ERROR   = 3;

	public const ACTION_AGENT_API                   = 'oco.computer.agent';
	public const ACTION_AGENT_API_RAW               = 'oco.computer.agent.api.rawrequest';
	public const ACTION_AGENT_API_HELLO             = 'oco.computer.agent.hello';
	public const ACTION_AGENT_API_UPDATE            = 'oco.computer.agent.update';
	public const ACTION_AGENT_API_UPDATE_JOB_STATE  = 'oco.computer.agent.update_job_state';

	public const ACTION_CLIENT_API_RAW = 'oco.client.api.rawrequest';
	public const ACTION_CLIENT_API     = 'oco.client.api';
	public const ACTION_CLIENT_WEB     = 'oco.client.web';

}
