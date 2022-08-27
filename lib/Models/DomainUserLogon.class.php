<?php

namespace Models;

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
	public $domain_user_display_name;

}
