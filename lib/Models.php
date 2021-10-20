<?php
class ComputerCommand {
	public $id;
	public $icon;
	public $name;
	public $description;
	public $command;
}
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
	public $serial;
	public $manufacturer;
	public $model;
	public $bios_version;
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
}
class ComputerNetwork {
	public $id;
	public $computer_id;
	public $nic_number;
	public $addr;
	public $netmask;
	public $broadcast;
	public $mac;
	public $interface;
}
class ComputerScreen {
	public $id;
	public $computer_id;
	public $name;
	public $manufacturer;
	public $type;
	public $resolution;
	public $size;
	public $manufactured;
	public $serialno;
}
class ComputerPrinter {
	public $id;
	public $computer_id;
	public $name;
	public $driver;
	public $paper;
	public $dpi;
	public $uri;
	public $status;
}
class ComputerPartition {
	public $id;
	public $computer_id;
	public $device;
	public $mountpoint;
	public $filesystem;
	public $size;
	public $free;
}
class ComputerSoftware {
	public $id;
	public $computer_id;
	public $software_id;
	public $version;
	public $installed;
	// joined software attributes
	public $software_name;
	public $software_description;
}
class ComputerPackage {
	public $id;
	public $computer_id;
	public $package_id;
	public $installed_procedure;
	public $installed;
	// joined computer attributes
	public $computer_hostname;
	// joined package attributes
	public $package_name;
	public $package_version;
}
class ComputerGroup {
	public $id;
	public $name;
}
class PackageFamily {
	public $id;
	public $name;
	public $notes;
	public $icon;
	// aggregated values
	public $package_count;
	public $newest_package_created;
	public $oldest_package_created;
	// functions
	function getIcon() {
		if(!empty($this->icon)) {
			return 'data:image/png;base64,'.base64_encode($this->icon);
		}
		return 'img/package.dyn.svg';
	}
}
class Package {
	public $id;
	public $version;
	public $notes;
	public $author;
	public $install_procedure;
	public $install_procedure_success_return_codes;
	public $install_procedure_post_action;
	public $uninstall_procedure;
	public $uninstall_procedure_success_return_codes;
	public $download_for_uninstall;
	public $uninstall_procedure_post_action;
	public $compatible_os;
	public $compatible_os_version;
	public $created;
	public $last_update;
	// joined package group attributes
	public $package_group_member_sequence;
	// joined package family attributes
	public $package_family_id;
	public $package_family_icon;
	// constants
	public const POST_ACTION_NONE = 0;
	public const POST_ACTION_RESTART = 1;
	public const POST_ACTION_SHUTDOWN = 2;
	public const POST_ACTION_EXIT = 3;
	// functions
	function getIcon() {
		if(!empty($this->package_family_icon)) {
			return 'data:image/png;base64,'.base64_encode($this->package_family_icon);
		}
		return 'img/package.dyn.svg';
	}
	public function getFilePath() {
		$path = PACKAGE_PATH.'/'.intval($this->id).'.zip';
		if(!file_exists($path)) return false;
		else return $path;
	}
	public function getSize() {
		$path = $this->getFilePath();
		if(!$path) return false;
		return filesize($path);
	}
}
class PackageGroup {
	public $id;
	public $name;
}
class JobContainer {
	public $id;
	public $name;
	public $start_time;
	public $end_time;
	public $notes;
	public $wol_sent;
	public $sequence_mode;
	public $priority;
	public $created;
	// aggregated values
	public $last_update;
	// constants (= icon names)
	public const STATUS_SUCCEEDED = 'tick';
	public const STATUS_FAILED = 'error';
	public const STATUS_IN_PROGRESS = 'wait';
	public const STATUS_WAITING_FOR_START = 'schedule';
	public const SEQUENCE_MODE_IGNORE_FAILED = 0;
	public const SEQUENCE_MODE_ABORT_AFTER_FAILED = 1;
	public const RETURN_CODE_ABORT_AFTER_FAILED = -8888;
}
class Job {
	public $id;
	public $job_container_id;
	public $computer_id;
	public $package_id;
	public $package_procedure;
	public $success_return_codes;
	public $is_uninstall;
	public $download;
	public $post_action;
	public $post_action_timeout;
	public $sequence;
	public $state;
	public $return_code;
	public $message;
	public $last_update;
	// joined computer attributes
	public $computer_hostname;
	// joined package attributes
	public $package_name;
	public $package_version;
	// joined job container attributes
	public $job_container_start_time = 0;
	// constants
	public const STATUS_WAITING_FOR_CLIENT = 0;
	public const STATUS_FAILED = -1;
	public const STATUS_EXPIRED = -2;
	public const STATUS_OS_INCOMPATIBLE = -3;
	public const STATUS_PACKAGE_CONFLICT = -4;
	public const STATUS_DOWNLOAD_STARTED = 1;
	public const STATUS_EXECUTION_STARTED = 2;
	public const STATUS_SUCCEEDED = 3;
	// functions
	function getIcon() {
		if($this->state == self::STATUS_WAITING_FOR_CLIENT) {
			$startTimeParsed = strtotime($this->job_container_start_time);
			if($startTimeParsed !== false && $startTimeParsed > time()) return 'img/schedule.dyn.svg';
			else return 'img/wait.dyn.svg';
		}
		if($this->state == self::STATUS_DOWNLOAD_STARTED) return 'img/downloading.dyn.svg';
		if($this->state == self::STATUS_EXECUTION_STARTED) return 'img/pending.dyn.svg';
		if($this->state == self::STATUS_FAILED) return 'img/error.dyn.svg';
		if($this->state == self::STATUS_EXPIRED) return 'img/timeout.dyn.svg';
		if($this->state == self::STATUS_OS_INCOMPATIBLE) return 'img/error.dyn.svg';
		if($this->state == self::STATUS_PACKAGE_CONFLICT) return 'img/error.dyn.svg';
		if($this->state == self::STATUS_SUCCEEDED) return 'img/tick.dyn.svg';
		return 'warning';
	}
	function getStateString() {
		$returnCodeString = '';
		if($this->return_code != null) {
			$returnCodeString = ' ('.htmlspecialchars($this->return_code).')';
		}
		if($this->state == self::STATUS_WAITING_FOR_CLIENT) {
			$startTimeParsed = strtotime($this->job_container_start_time);
			if($startTimeParsed !== false && $startTimeParsed > time()) return LANG['waiting_for_start'];
			return LANG['waiting_for_client'];
		}
		elseif($this->state == self::STATUS_FAILED)
			return LANG['failed'].$returnCodeString;
		elseif($this->state == self::STATUS_EXPIRED)
			return LANG['expired'];
		elseif($this->state == self::STATUS_OS_INCOMPATIBLE)
			return LANG['incompatible'];
		elseif($this->state == self::STATUS_PACKAGE_CONFLICT)
			return LANG['package_conflict'];
		elseif($this->state == self::STATUS_DOWNLOAD_STARTED)
			return LANG['download_started'];
		elseif($this->state == self::STATUS_EXECUTION_STARTED)
			return LANG['execution_started'];
		elseif($this->state == self::STATUS_SUCCEEDED)
			return LANG['succeeded'].$returnCodeString;
		else return $this->state;
	}
}
class Domainuser {
	public $id;
	public $username;
	// aggregated values
	public $logon_amount;
	public $computer_amount;
}
class DomainuserLogon {
	public $id;
	public $computer_id;
	public $domainuser_id;
	public $console;
	public $timestamp;
	// aggregated values
	public $logon_amount;
	public $computer_hostname;
	public $domainuser_username;
}
class Systemuser {
	public $id;
	public $username;
	public $fullname;
	public $password;
	public $ldap;
	public $email;
	public $phone;
	public $mobile;
	public $description;
	public $locked;
}
class Software {
	public $id;
	public $name;
	public $description;
	// aggregated values
	public $installations;
}
class Report {
	public $id;
	public $name;
	public $notes;
	public $query;
}
class ReportGroup {
	public $id;
	public $name;
}
class Log {
	public $id;
	public $level;
	public $user;
	public $realm;
	public $message;
	// constants
	public const LEVEL_DEBUG   = 0;
	public const LEVEL_INFO    = 1;
	public const LEVEL_WARNING = 2;
	public const LEVEL_ERROR   = 3;
}
