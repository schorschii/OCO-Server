<?php

namespace Models;

class Profile {

	public $id;
	public $name;
	public $payload;
	public $notes;
	public $created;
	public $created_by_system_user_id;
	public $last_update;

	// functions
	function getUuid() {
		$plist = new \CFPropertyList\CFPropertyList();
		$plist->parse($this->payload);
		return $plist->toArray()['PayloadUUID'] ?? null;
	}

}
