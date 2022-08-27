<?php

namespace Models;

class Computer {

	public $id;
	public $hostname;
	public $os;
	public $os_version;
	public $kernel_version;
	public $architecture;
	public $cpu;
	public $gpu;
	public $ram;
	public $agent_version;
	public $remote_address;
	public $serial;
	public $manufacturer;
	public $model;
	public $bios_version;
	public $uptime;
	public $boot_type;
	public $domain;
	public $secure_boot;
	public $last_ping;
	public $last_update;
	public $force_update;
	public $notes;
	public $agent_key;
	public $server_key;
	public $created;

	// joined software attributes
	public $software_version;

	// joined network attributes
	public $computer_network_mac;

	// functions
	function getIcon() {
		if(empty(trim($this->os))) return 'img/computer.dyn.svg';
		elseif(strpos($this->os, 'Windows') !== false) return 'img/windows.dyn.svg';
		elseif(strpos($this->os, 'macOS') !== false) return 'img/apple.dyn.svg';
		else return 'img/linux.dyn.svg';
	}
	function isOnline() {
		return time() - strtotime($this->last_ping) < COMPUTER_OFFLINE_SECONDS;
	}

}
