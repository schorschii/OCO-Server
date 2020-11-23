<?php

///// MySQL CONFIGURATION /////
const DB_HOST = 'localhost';
const DB_NAME = 'ocm';
const DB_USER = 'ocm';
const DB_PASS = 'PASSWORD';

///// LDAP CONFIGURATION (optional) /////
/*
 If you want to use LDAP user sync and login, please fill the following config lines.
 Otherwise, please set LDAP_SERVER to »null«.

 To sync LDAP users with the MASTERPLAN database, please execute "php lib/ldapsync.php" (via Cron)

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

// Example: 'CN=MasterplanUsers,OU=Benutzer,DC=sieber,DC=systems' or null
const LDAP_SYNC_GROUP = null;

///// GENERAL CONFIGURATION /////
const PACKAGE_PATH = '/var/www/oco/payload';
const MOTD = [
	"Have you tried turning it off and on again?",
	"The fact that ACPI was designed by a group of monkeys high on LSD, and is some of the worst designs in the industry obviously makes running it at any point pretty damn ugly. ~ Torvalds, Linus",
	"Microsoft isn't evil, they just make really crappy operating systems. ~ Torvalds, Linus",
	"XML is crap. Really. There are no excuses. XML is nasty to parse for humans, and it's a disaster to parse even for computers. There's just no reason for that horrible crap to exist. ~ Torvalds, Linus",
	"The memory management on the PowerPC can be used to frighten small children. ~ Torvalds, Linus",
	"Software is like sex; it's better when it's free. ~ Torvalds, Linus",
	"Now, most of you are probably going to be totally bored out of your minds on Christmas day, and here's the perfect distraction. Test 2.6.15-rc7. All the stores will be closed, and there's really nothing better to do in between meals. ~ Torvalds, Linus"
];
