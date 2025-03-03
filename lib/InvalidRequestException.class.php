<?php

class InvalidRequestException extends Exception {
	
	// Exception which is thrown if a user computer sends an invalid request

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
