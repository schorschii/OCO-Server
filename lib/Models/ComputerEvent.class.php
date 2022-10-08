<?php

namespace Models;

class ComputerEvent {

	public $id;
	public $computer_id;
	public $log;
	public $timestamp;
	public $provider;
	public $level;
	public $event_id;
	public $data;

	// functions
	public function getLevelText() {
		if($this->level == 1) return 'CRITICAL';
		if($this->level == 2) return 'ERROR';
		if($this->level == 3) return 'WARNING';
		if($this->level == 4) return 'INFO';
		if($this->level == 5) return 'VERBOSE';
		return $this->level;
	}
	public function getLevelClass() {
		if($this->level == 1) return 'critical';
		if($this->level == 2) return 'error';
		if($this->level == 3) return 'warning';
		if($this->level == 4) return 'info';
		if($this->level == 5) return 'verbose';
		return '';
	}

}
