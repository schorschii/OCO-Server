<?php

namespace Models;

class ComputerService {

	public $id;
	public $computer_id;
	public $timestamp;
	public $updated;
	public $status;
	public $name;
	public $metrics;
	public $details;

	// aggregated values
	public $history_count;

	// functions
	public function getStatusText() {
		if($this->status == 0) return 'OK';
		if($this->status == 1) return 'WARN';
		if($this->status == 2) return 'CRIT';
		if($this->status == 3) return 'UNKNOWN';
		return $this->status;
	}
	public function getStatusClass() {
		if($this->status == 0) return 'ok';
		if($this->status == 1) return 'warn';
		if($this->status == 2) return 'crit';
		if($this->status == 3) return 'unknown';
		return '';
	}

}
