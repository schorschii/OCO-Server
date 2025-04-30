<?php

namespace Models;

class ManagedApp {

	const TYPE_IOS = 'ios';
	const TYPE_ANDROID = 'android';

	public $id;
	public $identifier;
	public $store_id;
	public $name;
	public $vpp_amount;
	public $configurations;

	function getConfigurations() {
		return json_decode($this->configurations??'{}', true);
	}

}
