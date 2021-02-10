<?php
class ComputerCommand {
	public $id;
	public $icon;
	public $name;
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
	public $secure_boot;
	public $last_ping;
	public $last_update;
	public $notes;
	public $agent_key;
	// joined software attributes
	public $software_version;
	// joined network attributes
	public $computer_network_mac;
}
class ComputerNetwork {
	public $id;
	public $computer_id;
	public $nic_number;
	public $addr;
	public $netmask;
	public $broadcast;
	public $mac;
	public $domain;
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
class Package {
	public $id;
	public $name;
	public $notes;
	public $version;
	public $author;
	public $install_procedure;
	public $install_procedure_success_return_codes;
	public $uninstall_procedure;
	public $uninstall_procedure_success_return_codes;
	public $download_for_uninstall;
	public $created;
	// joined package group attributes
	public $package_group_member_sequence;

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
	public $created;
	// aggregated values
	public $last_update;
	// constants (= icon names)
	public const STATUS_SUCCEEDED = 'tick';
	public const STATUS_FAILED = 'error';
	public const STATUS_IN_PROGRESS = 'wait';
}
class Job {
	public $id;
	public $job_container_id;
	public $computer_id;
	public $package_id;
	public $package_procedure;
	public $success_return_codes;
	public $is_uninstall;
	public $sequence;
	public $state;
	public $message;
	public $last_update;
	// joined computer attributes
	public $computer_hostname;
	// joined package attributes
	public $package_name;
	public $package_version;
	// constants
	public const STATUS_WAITING_FOR_CLIENT = 0;
	public const STATUS_FAILED = -1;
	public const STATUS_EXPIRED = -2;
	public const STATUS_DOWNLOAD_STARTED = 1;
	public const STATUS_EXECUTION_STARTED = 2;
	public const STATUS_SUCCEEDED = 3;
	// functions
	function getIcon() {
		if($this->state == self::STATUS_WAITING_FOR_CLIENT || $this->state == self::STATUS_DOWNLOAD_STARTED || $this->state == self::STATUS_EXECUTION_STARTED) {
			return 'wait';
		}
		if($this->state == self::STATUS_FAILED || $this->state == self::STATUS_EXPIRED) {
			return 'error';
		}
		if($this->state == self::STATUS_SUCCEEDED) {
			return 'tick';
		}
		return 'warning';
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
