<?php

namespace Models;

class Profile {

	const TYPE_IOS = 'ios';
	const TYPE_IOS_DECLARATION = 'ios-dec';
	const TYPE_ANDROID = 'android';

	public $id;
	public $type;
	public $name;
	public $declaration_type;
	public $payload;
	public $notes;
	public $created;
	public $created_by_system_user_id;
	public $updated;
	public $updated_by_system_user_id;

	// functions
	function getUuid() {
		try {
			$plist = new \CFPropertyList\CFPropertyList();
			$plist->parse($this->payload);
			return $plist->toArray()['PayloadUUID'] ?? null;
		} catch(\Exception $e) {}
	}
	function getPayloadIdentifier() {
		try {
			$plist = new \CFPropertyList\CFPropertyList();
			$plist->parse($this->payload);
			return $plist->toArray()['PayloadIdentifier'] ?? null;
		} catch(\Exception $e) {}
	}
	function getToken() {
		return md5($this->id.'_'.($this->updated??$this->created));
	}

}
