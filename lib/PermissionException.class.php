<?php

class PermissionException extends Exception {

	// Exception which is thrown if a user or computer does not have permission for a specific resource

	public $action;
	public $user;
	public $objectId;

	public function __construct(string $message='', string $action=null, string $user=null, int $objectId=null) {
		parent::__construct($message);
		$this->action = $action;
		$this->user = $user;
		$this->objectId = $objectId;
	}

}
