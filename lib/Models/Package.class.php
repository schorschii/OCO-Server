<?php

namespace Models;

class Package {

	function __construct($binaryAsBase64=false) {
		if($binaryAsBase64 && !empty($this->package_family_icon))
			$this->package_family_icon = base64_encode($this->package_family_icon);
	}

	// attributes
	public $id;
	public $version;
	public $compatible_os;
	public $compatible_os_version;
	public $notes;
	public $install_procedure;
	public $install_procedure_success_return_codes;
	public $install_procedure_post_action;
	public $installation_removes_previous_versions;
	public $uninstall_procedure;
	public $uninstall_procedure_success_return_codes;
	public $download_for_uninstall;
	public $uninstall_procedure_post_action;
	public $created;
	public $created_by_system_user_id;
	public $last_update;

	// joined package group attributes
	public $package_group_member_sequence;

	// joined package family attributes
	public $package_family_id;
	public $package_family_name;
	public $package_family_icon;

	// joined system user attributes
	public $created_by_system_user_username;

	// constants
	public const POST_ACTION_NONE = 0;
	public const POST_ACTION_RESTART = 1;
	public const POST_ACTION_SHUTDOWN = 2;
	public const POST_ACTION_EXIT = 3;

	// functions
	function getFullName() {
		return $this->package_family_name.' ('.$this->version.')';
	}
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
	public function download($resumable=false) {
		$path = $this->getFilePath();
		if(!$path) throw new \NotFoundException();
		if($resumable) {
			// use resumable download if requested
			try {
				$download = new \ResumeDownload($path);
				$download->process();
			} catch(Exception $e) {
				throw new \NotFoundException();
			}
		} else {
			// use direct download via readfile() by default because its much faster
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: Binary');
			header('Content-disposition: attachment; filename="'.basename($path).'"');
			header('Content-Length: '.filesize($path));
			ob_clean(); flush();
			readfile($path);
		}
	}
	public function getContentListing() {
		$contents = [];
		$filePath = $this->getFilePath();
		if(!$filePath) return false;
		$zip = new \ZipArchive();
		$res = $zip->open($filePath);
		if($res) {
			$i = 0;
			while(!empty($zip->statIndex($i)['name'])) {
				$contents[$zip->statIndex($i)['name']] = $zip->statIndex($i)['size'];
				$i ++;
			}
		}
		return $contents;
	}

}
