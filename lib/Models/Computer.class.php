<?php

namespace Models;

class Computer {

	public $id;
	public $uid;
	public $hostname;
	public $os;
	public $os_version;
	public $os_license;
	public $os_locale;
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
	function isOnline($db) {
		if(!$db instanceof \DatabaseController) throw new Exception('Missing DatabaseController Reference');
		return time() - strtotime($this->last_ping??0) < intval($db->selectSettingByKey('computer-offline-seconds'));
	}

	const DEFAULT_COMPUTER_COMMANDS = [
		['icon'=>'img/screen-access.dyn.svg', 'name'=>'VNC', 'description'=>'client_extension_note', 'command'=>'vnc://$$TARGET$$', 'new_tab'=>false],
		['icon'=>'img/screen-access.dyn.svg', 'name'=>'RDP', 'description'=>'client_extension_note', 'command'=>'rdp://$$TARGET$$', 'new_tab'=>false],
		['icon'=>'img/screen-access.dyn.svg', 'name'=>'SSH', 'description'=>'client_extension_note', 'command'=>'ssh://$$TARGET$$', 'new_tab'=>false],
		['icon'=>'img/ping.dyn.svg', 'name'=>'Ping', 'description'=>'client_extension_note', 'command'=>'ping://$$TARGET$$', 'new_tab'=>false],
		['icon'=>'img/portscan.dyn.svg', 'name'=>'Nmap', 'description'=>'client_extension_note', 'command'=>'nmap://$$TARGET$$', 'new_tab'=>false],
	];
	static function getCommands(\ExtensionController $ext) {
		$extensionCommands = $ext->getAggregatedConf('computer-commands');
		return array_merge(self::DEFAULT_COMPUTER_COMMANDS, $extensionCommands);
	}

}
