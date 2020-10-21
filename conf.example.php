<?php

///// MySQL CONFIGURATION /////
const DB_HOST = 'localhost';
const DB_NAME = 'ocm';
const DB_USER = 'ocm';
const DB_PASS = 'PASSWORD';


///// GENERAL CONFIGURATION /////
const PACKAGE_PATH = '/var/www/oco/payload';


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
