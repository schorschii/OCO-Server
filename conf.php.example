<?php

/////////////////////////////////
///// GENERAL CONFIGURATION /////
/////////////////////////////////
const PACKAGE_PATH                    = '/var/www/oco/depot';  // path for uploaded software packages


///////////////////////////////
///// MySQL CONFIGURATION /////
///////////////////////////////
const DB_TYPE = 'mysql';
const DB_PORT = '3306';
const DB_HOST = 'localhost';
const DB_NAME = 'oco';
const DB_USER = 'oco';
const DB_PASS = 'PASSWORD';


//////////////////////////////////////////////////
///// SATELLITE WOL CONFIGURATION (optional) /////
//////////////////////////////////////////////////
/*
 If you want to use Wake On Lan (WOL) on foreign networks (networks, in which your OCO server
 does not have a network card) you can configure the satellite WOL technology.

 With satellite WOL, the OCO server will contact another server in the target network via SSH executing the "wakeonlan" command.

 Please make sure that the remote server can be accessed with the defined SSH key and that "wakeonlan" is installed.
*/
const SATELLITE_WOL_SERVER = [
	# [
	# 	'ADDRESS' => 'remoteserver01',
	# 	'PORT' => 22,
	# 	'USER' => 'root',
	# 	'PRIVKEY' => '/path/to/id_rsa',
	# 	'PUBKEY' => '/path/to/id_rsa.pub',
	# 	'COMMAND' => null, // if »null« OCO uses the default command "wakeonlan"
	# ],
	// more server here...
];


////////////////////////////////////
///// ADDITIONAL CONFIGURATION /////
////////////////////////////////////

/* strings randomly shown on the login page */
const LOGIN_SCREEN_QUOTES = [
	"Have you tried turning it off and on again?",
	"The fact that ACPI was designed by a group of monkeys high on LSD, and is some of the worst designs in the industry obviously makes running it at any point pretty damn ugly. ~ Torvalds, Linus",
	"Microsoft isn't evil, they just make really crappy operating systems. ~ Torvalds, Linus",
	"XML is crap. Really. There are no excuses. XML is nasty to parse for humans, and it's a disaster to parse even for computers. There's just no reason for that horrible crap to exist. ~ Torvalds, Linus",
	"The memory management on the PowerPC can be used to frighten small children. ~ Torvalds, Linus",
	"Software is like sex; it's better when it's free. ~ Torvalds, Linus",
	"Now, most of you are probably going to be totally bored out of your minds on Christmas day, and here's the perfect distraction. Test 2.6.15-rc7. All the stores will be closed, and there's really nothing better to do in between meals. ~ Torvalds, Linus",
	"If you didn't get angry and mad and frustrated, that means you don't care about the end result, and are doing something wrong. ~ Kroah-Hartman, Greg",
	"Coffee pots heat water using electronic mechanisms, so there is no fire. Thus, no firewalls are necessary, and firewall control policy is irrelevant. ~ RFC 2324",
	"Future versions of this protocol may include extensions for espresso machines and similar devices. ~ RFC 2324",
	"Implementers should be aware that excessive use of the Sugar addition may cause the BREW request to exceed the segment size allowed by the transport layer, causing fragmentation and a delay in brewing. ~ RFC 7168",
	"It has been observed that some users of blended teas have an occasional preference for teas brewed as an emulsion of cane sugar with hints of water. ~ RFC 7168",
	"Packets cannot feel. They are created for the purpose of moving data from one system to another. However, it is clear that in specific situations some measure of emotion can be inferred or added. ~ RFC 5841",
	"An HTJP request MUST be an HTTP response message. An HTJP response message MUST be an HTTP request message that, if issued to the appropriate HTTP server, would elicit the HTTP response specified by the HTJP request being replied to. ~ RFC 8568",
	"Some applications hand-craft their own packets. If these packets are part of an attack, the application MUST set the evil bit by itself. Devices such as firewalls MUST drop all inbound packets that have the evil bit set. ~ RFC 3514",
	"Don't drink and code.",
	"10 HOME<br>20 SWEET<br>30 GOTO 10",
];

/* message on the home page (use 'default_motd' for the vendor default text) */
const MOTD = 'default_motd';

/* various UI default settings */
const DEFAULTS = [
	// deployment page defaults
	'default-use-wol' => false,
	'default-shutdown-waked-after-completion' => false,
	'default-restart-timeout' => 20,
	'default-auto-create-uninstall-jobs' => true,
	'default-force-install-same-version' => false,
	'default-abort-after-error' => false,
];
