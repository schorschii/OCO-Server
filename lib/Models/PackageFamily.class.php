<?php

namespace Models;

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
	public $license_count;
	public $notes;
	public $icon;

	// aggregated values
	public $package_count;
	public $install_count;
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
