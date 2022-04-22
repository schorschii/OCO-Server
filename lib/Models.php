<?php
class Stat {
	public $domain_users;
	public $computers;
	public $packages;
	public $job_containers;
	public $reports;
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
	public $installed_by;
	public $installed_procedure;
	public $installed;
	// joined computer attributes
	public $computer_hostname;
	// joined package attributes
	public $package_family_name;
	public $package_version;
}
class ComputerGroup {
	public $id;
	public $parent_computer_group_id;
	public $name;
}
class PackageFamily {
	function __construct($binaryAsBase64=false) {
		if($binaryAsBase64 && !empty($this->icon))
			$this->icon = base64_encode($this->icon);
	}
	public static function __constructWithId($id) {
		$item = new PackageFamily();
		$item->id = $id;
		return $item;
	}
	// attributes
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
		return 'img/package-family.dyn.svg';
	}
}
class Package {
	function __construct($binaryAsBase64=false) {
		if($binaryAsBase64 && !empty($this->package_family_icon))
			$this->package_family_icon = base64_encode($this->package_family_icon);
	}
	// attributes
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
	public $package_family_name;
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
	public function getContentListing() {
		$filePath = $this->getFilePath();
		if(!$filePath) return '';
		$output = ''; $size = 0;
		$zip = new ZipArchive();
		$res = $zip->open($filePath);
		if($res) {
			$i = 0;
			while(!empty($zip->statIndex($i)['name'])) {
				$output .= $zip->statIndex($i)['name']." (".niceSize($zip->statIndex($i)['size']).")<br>\n";
				$size += $zip->statIndex($i)['size'];
				$i ++;
			}
			$output .= "=========================<br>\n";
			$output .= niceSize($size);
		}
		return $output;
	}
}
class PackageGroup {
	public $id;
	public $parent_package_group_id;
	public $name;
}
class JobContainer {
	public $id;
	public $name;
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
	public $wol_shutdown_set;
	public $last_update;
	// joined computer attributes
	public $computer_hostname;
	// joined package attributes
	public $package_family_name;
	public $package_version;
	// joined job container attributes
	public $job_container_start_time = 0;
	public $job_container_author;
	// constants
	public const STATUS_WAITING_FOR_CLIENT = 0;
	public const STATUS_FAILED = -1;
	public const STATUS_EXPIRED = -2;
	public const STATUS_OS_INCOMPATIBLE = -3;
	public const STATUS_PACKAGE_CONFLICT = -4;
	public const STATUS_ALREADY_INSTALLED = -5;
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
		return 'img/warning.dyn.svg';
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
		elseif($this->state == self::STATUS_ALREADY_INSTALLED)
			return LANG['already_installed'];
		elseif($this->state == self::STATUS_DOWNLOAD_STARTED)
			return LANG['download_started'];
		elseif($this->state == self::STATUS_EXECUTION_STARTED)
			return LANG['execution_started'];
		elseif($this->state == self::STATUS_SUCCEEDED)
			return LANG['succeeded'].$returnCodeString;
		else return $this->state;
	}
}
class DomainUser {
	public $id;
	public $username;
	// aggregated values
	public $logon_amount;
	public $computer_amount;
}
class DomainUserLogon {
	public $id;
	public $computer_id;
	public $domain_user_id;
	public $console;
	public $timestamp;
	// aggregated values
	public $logon_amount;
	public $computer_hostname;
	public $domain_user_username;
}
class SystemUser {
	private $db;
	public function __construct(DatabaseController $db) {
		$this->db = $db;
	}
	// attributes
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
	public $last_login;
	public $created;
	public $system_user_role_id;
	// joined system user role attributes
	public $system_user_role_name;
	public $system_user_role_permissions;
	// permission implementation
	private $pm;
	function checkPermission($ressource, String $method, Bool $throw=true) {
		if($this->pm === null) $this->pm = new PermissionManager($this->db, $this);
		$checkResult = $this->pm->hasPermission($ressource, $method);
		if(!$checkResult && $throw) throw new PermissionException();
		return $checkResult;
	}
}
class SystemUserRole {
	public $id;
	public $name;
	public $permissions;
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
	public $report_group_id;
	public $name;
	public $notes;
	public $query;
}
class ReportGroup {
	public $id;
	public $parent_report_group_id;
	public $name;
}
class Log {
	public $id;
	public $level;
	public $user;
	public $object_id;
	public $action;
	public $data;
	// constants
	public const LEVEL_DEBUG   = 0;
	public const LEVEL_INFO    = 1;
	public const LEVEL_WARNING = 2;
	public const LEVEL_ERROR   = 3;

	public const ACTION_AGENT_API_RAW                   = 'oco.agent.api.rawrequest';
	public const ACTION_AGENT_API_HELLO                 = 'oco.agent.hello';
	public const ACTION_AGENT_API_UPDATE                = 'oco.agent.update';
	public const ACTION_AGENT_API_UPDATE_DEPLOY_STATUS  = 'oco.agent.update_deploy_status';

	public const ACTION_CLIENT_API_RAW = 'oco.client.api.rawrequest';
	public const ACTION_CLIENT_API     = 'oco.client.api';
	public const ACTION_CLIENT_WEB     = 'oco.client.web';
}
