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
	public function getLevelText($osType) {
		if($osType == Computer::OS_TYPE_WINDOWS) {
			if($this->level == 1) return 'CRITICAL';
			if($this->level == 2) return 'ERROR';
			if($this->level == 3) return 'WARNING';
			if($this->level == 4) return 'INFO';
			if($this->level == 5) return 'VERBOSE';
		} elseif($osType == Computer::OS_TYPE_LINUX) {
			if($this->level == 0) return 'EMERGENCY';
			if($this->level == 1) return 'ALERT';
			if($this->level == 2) return 'CRITICAL';
			if($this->level == 3) return 'ERROR';
			if($this->level == 4) return 'WARNING';
			if($this->level == 5) return 'NOTICE';
			if($this->level == 6) return 'INFO';
			if($this->level == 7) return 'DEBUG';
		}
		return $this->level;
	}
	public function getLevelClass($osType) {
		if($osType == Computer::OS_TYPE_WINDOWS) {
			if($this->level == 1) return 'critical';
			if($this->level == 2) return 'error';
			if($this->level == 3) return 'warning';
			if($this->level == 4) return 'info';
			if($this->level == 5) return 'verbose';
		} elseif($osType == Computer::OS_TYPE_LINUX) {
			if($this->level == 0) return 'critical';
			if($this->level == 1) return 'critical';
			if($this->level == 2) return 'error';
			if($this->level == 3) return 'error';
			if($this->level == 4) return 'warning';
			if($this->level == 5) return 'info';
			if($this->level == 6) return 'info';
			if($this->level == 7) return 'info';
		}
		return '';
	}

}
