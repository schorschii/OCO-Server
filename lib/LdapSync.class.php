<?php

class LdapSync {

	private /*DatabaseController*/ $db;
	private /*bool*/ $debug;

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->debug = $debug;
	}

	public function syncSystemUsers() {
		if(!LDAP_SERVER) {
			throw new Exception('LDAP sync not configured');
		}

		$ldapconn = ldap_connect(LDAP_SERVER);
		if(!$ldapconn) {
			throw new Exception('ldap_connect failed');
		}

		if($this->debug) echo "<=== ldap_connect OK ===>\n";
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0 );
		$ldapbind = ldap_bind($ldapconn, LDAP_USER.'@'.LDAP_DOMAIN, LDAP_PASS);
		if(!$ldapbind) {
			throw new Exception('ldap_bind failed: '.ldap_error($ldapconn));
		}

		if($this->debug) echo "<=== ldap_bind OK ===>\n";
		$result = ldap_search($ldapconn, LDAP_QUERY_ROOT, "(objectClass=".ldap_escape(LDAP_USER_CLASS).")");
		if(!$result) {
			throw new Exception('ldap_search failed: '.ldap_error($ldapconn));
		}

		$data = ldap_get_entries($ldapconn, $result);

		if($this->debug) echo "<=== ldap_search OK - processing ".$data["count"]." entries... ===>\n";

		// iterate over results array
		$foundLdapUsers = [];
		$counter = 1;
		for($i=0; $i<$data["count"]; $i++) {
			#var_dump($data[$i]); die(); // debug

			if(empty($data[$i][LDAP_ATTR_UID][0])) {
				continue;
			}
			$uid         = GUIDtoStr($data[$i][LDAP_ATTR_UID][0]);
			if(array_key_exists($uid, $foundLdapUsers)) {
				throw new Exception('Duplicate UID '.$uid.'!');
			}

			// parse LDAP values
			$username    = $data[$i][LDAP_ATTR_USERNAME][0];
			$firstname   = "?";
			$lastname    = "?";
			$displayname = "?";
			$mail        = null;
			$phone       = null;
			$mobile      = null;
			$description = null;
			$locked      = 0;
			if(isset($data[$i][LDAP_ATTR_FIRST_NAME][0]))
				$firstname = $data[$i][LDAP_ATTR_FIRST_NAME][0];
			if(isset($data[$i][LDAP_ATTR_LAST_NAME][0]))
				$lastname = $data[$i][LDAP_ATTR_LAST_NAME][0];
			if(isset($data[$i][LDAP_ATTR_DISPLAY_NAME][0]))
				$displayname = $data[$i][LDAP_ATTR_DISPLAY_NAME][0];
			if(isset($data[$i][LDAP_ATTR_EMAIL][0]))
				$mail = $data[$i][LDAP_ATTR_EMAIL][0];
			if(isset($data[$i][LDAP_ATTR_PHONE][0]))
				$phone = $data[$i][LDAP_ATTR_PHONE][0];
			if(isset($data[$i][LDAP_ATTR_MOBILE][0]))
				$mobile = $data[$i][LDAP_ATTR_MOBILE][0];
			if(isset($data[$i][LDAP_ATTR_DESCRIPTION][0]))
				$description = $data[$i][LDAP_ATTR_DESCRIPTION][0];
			#if(isset($data[$i]["useraccountcontrol"][0]))
			#	$locked = (intval($data[$i]["useraccountcontrol"][0]) & 2) ? 1 : 0;
			/* We currently do not modify the OCO locked flag via LDAP sync.
			   If the user is disabled in AD, then the login function (which tries an LDAP simple bind) will fail anyway. */

		   // check group membership and determine role ID
			$groupCheck = null;
			if(empty(LDAP_GROUPS)) {
				$groupCheck = LDAP_DEFAULT_ROLE_ID;
			} else if(isset($data[$i]["memberof"])) {
				for($n=0; $n<$data[$i]["memberof"]["count"]; $n++) {
					foreach(LDAP_GROUPS as $ldapGroupPath => $roleId) {
						if($data[$i]["memberof"][$n] == $ldapGroupPath) {
							$groupCheck = $roleId;
							break 2;
						}
					}
				}
			}
			if(!$groupCheck) {
				if($this->debug) echo '-> '.$username.': skip because not in required group'."\n";
				continue;
			}

			// add to found array
			$foundLdapUsers[$uid] = $username;

			// check if user already exists
			$id = null;
			$checkResult = $this->db->selectSystemUserByUid($uid);
			if(empty($checkResult)) {
				// fallback for old DB schema without uid
				$tmpCheckResult = $this->db->selectSystemUserByUsername($username);
				if(!empty($tmpCheckResult) && empty($tmpCheckResult->uid)) {
					$checkResult = $tmpCheckResult;
				}
			}
			if($checkResult !== null) {
				$id = $checkResult->id;
				if($this->debug) echo '--> '.$username.': found in db - update id: '.$id;

				// update into db
				if($this->db->updateSystemUser($id, $uid, $username, $displayname, null/*password*/, 1/*ldap-flag*/, $mail, $phone, $mobile, $description, $locked, $groupCheck))
					if($this->debug) echo "  OK\n";
				else throw new Exception('Error updating: '.$this->db->getLastStatement()->error);
			} else {
				if($this->debug) echo '--> '.$username.': not found in db - creating';

				// insert into db
				if($this->db->insertSystemUser($uid, $username, $displayname, null/*password*/, 1/*ldap-flag*/, $mail, $phone, $mobile, $description, $locked, $groupCheck))
					if($this->debug) echo "  OK\n";
				else throw new Exception('Error inserting: '.$this->db->getLastStatement()->error);
			}
			$counter ++;
		}
		ldap_close($ldapconn);

		if($this->debug) echo "<=== Check For Deleted Users... ===>\n";
		foreach($this->db->selectAllSystemUser() as $dbUser) {
			if($dbUser->ldap != 1) continue;
			$found = false;
			foreach($foundLdapUsers as $uid => $username) {
				if($dbUser->uid == $uid) {
					$found = true;
				}
				if($dbUser->username == $username) { // fallback for old DB schema without uid
					$found = true;
				}
			}
			if(!$found) {
				if($this->db->deleteSystemUser($dbUser->id)) {
					if($this->debug) echo '--> '.$dbUser->username.': deleting  OK'."\n";
				}
				else throw new Exception('Error deleting '.$dbUser->username.': '.$this->db->getLastStatement()->error);
			}
		}
	}

	public function syncDomainUsers() {
		if(!defined('SELF_SERVICE_ENABLED') || !SELF_SERVICE_ENABLED) {
			echo LANG('self_service_is_disabled')."\n";
			return;
		}

		if(!LDAP_SERVER) {
			throw new Exception('LDAP sync not configured');
		}

		$ldapconn = ldap_connect(LDAP_SERVER);
		if(!$ldapconn) {
			throw new Exception('ldap_connect failed');
		}

		if($this->debug) echo "<=== ldap_connect OK ===>\n";
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0 );
		$ldapbind = ldap_bind($ldapconn, LDAP_USER.'@'.LDAP_DOMAIN, LDAP_PASS);
		if(!$ldapbind) {
			throw new Exception('ldap_bind failed: '.ldap_error($ldapconn));
		}

		if($this->debug) echo "<=== ldap_bind OK ===>\n";
		$result = ldap_search($ldapconn, LDAP_QUERY_ROOT, "(objectClass=".ldap_escape(LDAP_USER_CLASS).")");
		if(!$result) {
			throw new Exception('ldap_search failed: '.ldap_error($ldapconn));
		}

		$data = ldap_get_entries($ldapconn, $result);

		if($this->debug) echo "<=== ldap_search OK - processing ".$data["count"]." entries... ===>\n";

		// iterate over results array
		$foundDomainUsers = [];
		$foundLdapUsers = [];
		$counter = 1;
		for($i=0; $i<$data["count"]; $i++) {
			#var_dump($data[$i]); die(); // debug

			if(empty($data[$i][LDAP_ATTR_UID][0])) {
				continue;
			}
			$uid         = GUIDtoStr($data[$i][LDAP_ATTR_UID][0]);
			if(array_key_exists($uid, $foundLdapUsers)) {
				throw new Exception('Duplicate UID '.$uid.'!');
			}

			// parse LDAP values
			$username    = $data[$i][LDAP_ATTR_USERNAME][0];
			$firstname   = "?";
			$lastname    = "?";
			$displayname = "?";
			$mail        = null;
			$phone       = null;
			$mobile      = null;
			$description = null;
			$locked      = 0;
			if(isset($data[$i][LDAP_ATTR_FIRST_NAME][0]))
				$firstname = $data[$i][LDAP_ATTR_FIRST_NAME][0];
			if(isset($data[$i][LDAP_ATTR_LAST_NAME][0]))
				$lastname = $data[$i][LDAP_ATTR_LAST_NAME][0];
			if(isset($data[$i][LDAP_ATTR_DISPLAY_NAME][0]))
				$displayname = $data[$i][LDAP_ATTR_DISPLAY_NAME][0];
			if(isset($data[$i][LDAP_ATTR_EMAIL][0]))
				$mail = $data[$i][LDAP_ATTR_EMAIL][0];
			if(isset($data[$i][LDAP_ATTR_PHONE][0]))
				$phone = $data[$i][LDAP_ATTR_PHONE][0];
			if(isset($data[$i][LDAP_ATTR_MOBILE][0]))
				$mobile = $data[$i][LDAP_ATTR_MOBILE][0];
			if(isset($data[$i][LDAP_ATTR_DESCRIPTION][0]))
				$description = $data[$i][LDAP_ATTR_DESCRIPTION][0];
			#if(isset($data[$i]["useraccountcontrol"][0]))
			#	$locked = (intval($data[$i]["useraccountcontrol"][0]) & 2) ? 1 : 0;
			/* We currently do not modify the OCO locked flag via LDAP sync.
			   If the user is disabled in AD, then the login function (which tries an LDAP simple bind) will fail anyway. */

		   // check group membership and determine role ID
			$groupCheck = null;
			if(empty(SELF_SERVICE_LDAP_GROUPS)) {
				$groupCheck = SELF_SERVICE_DEFAULT_ROLE_ID;
			} else if(isset($data[$i]["memberof"])) {
				for($n=0; $n<$data[$i]["memberof"]["count"]; $n++) {
					foreach(SELF_SERVICE_LDAP_GROUPS as $ldapGroupPath => $roleId) {
						if($data[$i]["memberof"][$n] == $ldapGroupPath) {
							$groupCheck = $roleId;
							break 2;
						}
					}
				}
			}
			if(!$groupCheck) {
				if($this->debug) echo '-> '.$username.': skip because not in required group'."\n";
				continue;
			}

			// add to found array
			$foundLdapUsers[$uid] = $username;

			// check if user already exists
			$id = null;
			$checkResult = $this->db->selectDomainUserByUid($uid);
			if(!$checkResult) {
				if($this->debug) echo '-> '.$username.': skip because UUID '.$uid.' not found in domain_user table'."\n";
				continue;
			} else {
				$id = $checkResult->id;
				$foundDomainUsers[] = $id;
				if($this->debug) echo '--> '.$username.': found in db with UUID '.$uid.' - update id: '.$id;

				// update into db
				if($this->db->updateDomainUser($id, $groupCheck, null, 1/*ldap*/))
					if($this->debug) echo "  OK\n";
				else throw new Exception('Error updating: '.$this->db->getLastStatement()->error);
			}
			$counter ++;
		}
		ldap_close($ldapconn);

		// set ldap=0 to all not found entries
		if($this->debug) echo '--> revoke access for deleted LDAP domain users';
		if($this->db->revokeAllLdapDomainUserByIds($foundDomainUsers))
			if($this->debug) echo "  OK\n";
		else throw new Exception('Error updating: '.$this->db->getLastStatement()->error);
	}

}
