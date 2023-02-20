<?php

class SettingsController {

    private $db;
    private $defaults = [
		'client-api-enabled' => 0, // enable/disable the api-client.php
		'client-api-key' => '<randomly-generated-by-setup>', // key for using the API

		'agent-self-registration-enabled' => 0, // enable/disable automatic/unattended agent registration
		'agent-registration-key' => '<randomly-generated-by-setup>', // agent registration key

		'computer-keep-inactive-screens' => false, // do not delete disconnected screens from database (to keep track of serial numbers)

		'self-service-enabled' => false,    // enable/disable the self service portal

		'computer-offline-seconds' => 125,  // seconds after last agent communication a computer is considered as offline
		'wol-shutdown-expiry' => 60*5,      // assume WOL did not worked after 5 minutes
		'agent-update-interval' => 60*60*2, // update computer details every 2 hours

		'purge-succeeded-jobs-after' => 60*60*4,
		'purge-failed-jobs-after' => 60*60*24*2,
		'purge-logs-after' => 60*60*24*14,
		'purge-domain-user-logons-after' => 60*60*24*365,
		'purge-events-after' => 60*60*24*7,

		'log-level' => 1, // 0 -> DEBUG, 1 -> INFO, 2 -> WARNING, 3 -> ERROR, 4 -> NO LOGGING
		'check-update' => true, // check for server updates when loading the web console
		'do-housekeeping-by-web-requests' => false, // db cleanup method - normally done via cron job but can be done on every web request (not recommended)

		// deployment page defaults
		'default-use-wol' => 0,
		'default-shutdown-waked-after-completion' => 0,
		'default-restart-timeout' => 20,
		'default-auto-create-uninstall-jobs' => 1,
		'default-force-install-same-version' => 0,
		'default-abort-after-error' => 0,

		'motd' => 'default_motd', // message of the day (homepage)
		'login-screen-quotes' => '["Have you tried turning it off and on again?","The fact that ACPI was designed by a group of monkeys high on LSD, and is some of the worst designs in the industry obviously makes running it at any point pretty damn ugly. ~ Torvalds, Linus","Microsoft isn\'t evil, they just make really crappy operating systems. ~ Torvalds, Linus","XML is crap. Really. There are no excuses. XML is nasty to parse for humans, and it\'s a disaster to parse even for computers. There\'s just no reason for that horrible crap to exist. ~ Torvalds, Linus","The memory management on the PowerPC can be used to frighten small children. ~ Torvalds, Linus","Software is like sex; it\'s better when it\'s free. ~ Torvalds, Linus","Now, most of you are probably going to be totally bored out of your minds on Christmas day, and here\'s the perfect distraction. Test 2.6.15-rc7. All the stores will be closed, and there\'s really nothing better to do in between meals. ~ Torvalds, Linus","If you didn\'t get angry and mad and frustrated, that means you don\'t care about the end result, and are doing something wrong. ~ Kroah-Hartman, Greg","Coffee pots heat water using electronic mechanisms, so there is no fire. Thus, no firewalls are necessary, and firewall control policy is irrelevant. ~ RFC 2324","Future versions of this protocol may include extensions for espresso machines and similar devices. ~ RFC 2324","Implementers should be aware that excessive use of the Sugar addition may cause the BREW request to exceed the segment size allowed by the transport layer, causing fragmentation and a delay in brewing. ~ RFC 7168","It has been observed that some users of blended teas have an occasional preference for teas brewed as an emulsion of cane sugar with hints of water. ~ RFC 7168","Packets cannot feel. They are created for the purpose of moving data from one system to another. However, it is clear that in specific situations some measure of emotion can be inferred or added. ~ RFC 5841","An HTJP request MUST be an HTTP response message. An HTJP response message MUST be an HTTP request message that, if issued to the appropriate HTTP server, would elicit the HTTP response specified by the HTJP request being replied to. ~ RFC 8568","Some applications hand-craft their own packets. If these packets are part of an attack, the application MUST set the evil bit by itself. Devices such as firewalls MUST drop all inbound packets that have the evil bit set. ~ RFC 3514","Don\'t drink and code.","10 HOME<br>20 SWEET<br>30 GOTO 10"]',
	];

    function __construct(DatabaseController $db, Array $additionalDefaults=[]) {
        $this->db = $db;
        $this->defaults = array_merge($this->defaults, $additionalDefaults);
    }

    public function get($key) {
        $value = null;
        $setting = $this->db->selectSettingByKey($key);
        if($setting === null && array_key_exists($key, $this->defaults)) {
			// apply defaults
			$value = $this->defaults[$key];
		} elseif($setting !== null) {
			$value = $setting->value;
		}
        return $value;
    }

}
