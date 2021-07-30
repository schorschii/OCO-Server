<?php

///// GENERAL CONFIGURATION /////
const PACKAGE_PATH             = '/var/www/oco/payload';
const COMPUTER_OFFLINE_SECONDS = 125;

const CLIENT_API_ENABLED       = false;
const CLIENT_API_KEY           = 'Ungah2oo';

const AGENT_SELF_REGISTRATION_ENABLED = true;
const AGENT_REGISTRATION_KEY          = 'ernesto';
const AGENT_UPDATE_INTERVAL           = 3600;

const PURGE_SUCCEEDED_JOBS_AFTER = 14400;
const PURGE_FAILED_JOBS_AFTER    = 172800;

const DELETE_LOGS_AFTER = 172800;
const LOG_LEVEL         = 2;
// 0 -> DEBUG
// 1 -> INFO
// 2 -> WARNING
// 3 -> ERROR
// 4 -> NO LOGGING

///// MySQL CONFIGURATION /////
const DB_TYPE = 'mysql';
const DB_PORT = '3306';
const DB_HOST = 'localhost';
const DB_NAME = 'oco';
const DB_USER = 'oco';
const DB_PASS = 'PASSWORD';

///// LDAP CONFIGURATION (optional) /////
/*
 If you want to use LDAP user sync and login, please fill the following config lines.
 Otherwise, please set LDAP_SERVER to »null«.

 To sync LDAP users with the OCO system user database, please execute "php lib/ldapsync.php" (via Cron)

 Set LDAP_SYNC_GROUP to »null« if you want to sync all users inside LDAP_QUERY_ROOT.
 Otherwise, insert the LDAP path of the group.
*/

// Example: 'ldap://192.168.56.101' (single) or 'ldaps://192.168.56.101' (secure) or 'ldaps://192.168.56.101 ldaps://192.168.56.102' (multiple) or null (disabled)
const LDAP_SERVER     = null;
const LDAP_USER       = '';
const LDAP_PASS       = '';

// Example: 'subdomain.domain.tld'
const LDAP_DOMAIN     = '';

// Example: 'OU=Benutzer,DC=sieber,DC=systems'
const LDAP_QUERY_ROOT = '';

// Example: 'CN=OcoUsers,OU=Benutzer,DC=sieber,DC=systems' or null
const LDAP_SYNC_GROUP = null;

///// SATELLITE WOL CONFIGURATION (optional) /////
/*
 If you want to use Wake On Lan (WOL) on foreign networks (networks, in which your OCO server
 does not have a network card) you can configure the satellite WOL technology.

 With satellite WOL, the OCO server will contact another server in the target network via SSH executing the "wakeonlan" command.

 Please make sure that the remote server can be accessed with the defined SSH key and that "wakeonlan" ist installed.
*/
const SATELLITE_WOL_SERVER = [
	#[
	#	'ADDRESS' => 'remoteserver01',
	#	'PORT' => 22,
	#	'USER' => 'root',
	#	'PRIVKEY' => '/path/to/id_rsa',
	#	'PUBKEY' => '/path/to/id_rsa.pub',
	#	'COMMAND' => null, // if »null« OCO uses the default command "wakeonlan"
	#],
	// more server here...
];

///// ADDITIONAL CONFIGURATION /////
const COMPUTER_COMMANDS = [
	['icon'=>'img/screen-access.svg', 'name'=>'VNC', 'description'=>'client_extension_note', 'command'=>'vnc://$$TARGET$$', 'new_tab'=>false],
	['icon'=>'img/screen-access.svg', 'name'=>'RDP', 'description'=>'client_extension_note', 'command'=>'rdp://$$TARGET$$', 'new_tab'=>false],
	['icon'=>'img/screen-access.svg', 'name'=>'SSH', 'description'=>'client_extension_note', 'command'=>'ssh://$$TARGET$$', 'new_tab'=>false],
	['icon'=>'img/ping.svg', 'name'=>'Ping', 'description'=>'client_extension_note', 'command'=>'ping://$$TARGET$$', 'new_tab'=>false],
	['icon'=>'img/portscan.svg', 'name'=>'Nmap', 'description'=>'client_extension_note', 'command'=>'nmap://$$TARGET$$', 'new_tab'=>false],
];
const MOTD = [
	"Have you tried turning it off and on again?",
	"The fact that ACPI was designed by a group of monkeys high on LSD, and is some of the worst designs in the industry obviously makes running it at any point pretty damn ugly. ~ Torvalds, Linus",
	"Microsoft isn't evil, they just make really crappy operating systems. ~ Torvalds, Linus",
	"XML is crap. Really. There are no excuses. XML is nasty to parse for humans, and it's a disaster to parse even for computers. There's just no reason for that horrible crap to exist. ~ Torvalds, Linus",
	"The memory management on the PowerPC can be used to frighten small children. ~ Torvalds, Linus",
	"Software is like sex; it's better when it's free. ~ Torvalds, Linus",
	"Now, most of you are probably going to be totally bored out of your minds on Christmas day, and here's the perfect distraction. Test 2.6.15-rc7. All the stores will be closed, and there's really nothing better to do in between meals. ~ Torvalds, Linus"
];
