<?php

namespace Models;

class StaticJob extends Job {

	// specific attributes
	public $job_container_id;

	// joined job container attributes
	public $job_container_start_time = 0;
	public $job_container_author;
	public $job_container_enabled;
	public $job_container_sequence_mode;
	public $job_container_priority;
	public $job_container_agent_ip_ranges;

}
