<?php

class LdapSync {

	private /*DatabaseController*/ $db;
	private /*bool*/ $debug;

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->debug = $debug;
	}

	private static function GUIDtoStr($binary_guid) {
		$unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
		if(!$unpacked) {
			// fallback string representation (base64) if we got unexpected input
			return base64_encode($binary_guid);
		}
		return sprintf('%08x-%04x-%04x-%04x-%04x%08x', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
	}

	public function syncSystemUsers() {
		if(!LDAP_SERVER) {
			throw new Exception('LDAP sync not configured');
		}

		// connect to server
		$availSystemUserRoleIds = array_map(function($o){return $o->id;}, $this->db->selectAllSystemUserRole());
		$ldapconn = ldap_connect(LDAP_SERVER);
		if(!$ldapconn) {
			throw new Exception('ldap_connect failed');
		}
		if($this->debug) echo "<=== ldap_connect OK ===>\n";

		// set options and authenticate
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0 );
		$ldapbind = ldap_bind($ldapconn, LDAP_USER.'@'.LDAP_DOMAIN, LDAP_PASS);
		if(!$ldapbind) {
			throw new Exception('ldap_bind failed: '.ldap_error($ldapconn));
		}
		if($this->debug) echo "<=== ldap_bind OK ===>\n";

		// ldap search with paging support
		$data = [];
		$cookie = null;
		do {
			$result = ldap_search(
				$ldapconn, LDAP_QUERY_ROOT, "(objectClass=".ldap_escape(LDAP_USER_CLASS).")",
				[] /*attributes*/, 0 /*attributes_only*/, -1 /*sizelimit*/, -1 /*timelimit*/, LDAP_DEREF_NEVER,
				[ ['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => 750, 'cookie' => $cookie]] ]
			);
			if(!$result) {
				throw new Exception('ldap_search failed: '.ldap_error($ldapconn));
			}
			ldap_parse_result($ldapconn, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);
			$data = array_merge($data, ldap_get_entries($ldapconn, $result));
			if(isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
				$cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
			} else {
				$cookie = null;
			}
		} while(!empty($cookie));
		if($this->debug) echo "<=== ldap_search OK - processing entries... ===>\n";

		// iterate over results array
		$foundLdapUsers = [];
		$counter = 1;
		foreach($data as $key => $account) {
			if(!is_numeric($key)) continue; // skip "count" entry
			#var_dump($account); die(); // debug

			if(empty($account[LDAP_ATTR_UID][0])) {
				continue;
			}
			$uid = self::GUIDtoStr($account[LDAP_ATTR_UID][0]);
			if(array_key_exists($uid, $foundLdapUsers)) {
				throw new Exception('Duplicate UID '.$uid.'!');
			}

			// parse LDAP values
			$username    = $account[LDAP_ATTR_USERNAME][0];
			$firstname   = "?";
			$lastname    = "?";
			$displayname = "?";
			$mail        = null;
			$phone       = null;
			$mobile      = null;
			$description = null;
			$locked      = 0;
			if(isset($account[LDAP_ATTR_FIRST_NAME][0]))
				$firstname = $account[LDAP_ATTR_FIRST_NAME][0];
			if(isset($account[LDAP_ATTR_LAST_NAME][0]))
				$lastname = $account[LDAP_ATTR_LAST_NAME][0];
			if(isset($account[LDAP_ATTR_DISPLAY_NAME][0]))
				$displayname = $account[LDAP_ATTR_DISPLAY_NAME][0];
			if(isset($account[LDAP_ATTR_EMAIL][0]))
				$mail = $account[LDAP_ATTR_EMAIL][0];
			if(isset($account[LDAP_ATTR_PHONE][0]))
				$phone = $account[LDAP_ATTR_PHONE][0];
			if(isset($account[LDAP_ATTR_MOBILE][0]))
				$mobile = $account[LDAP_ATTR_MOBILE][0];
			if(isset($account[LDAP_ATTR_DESCRIPTION][0]))
				$description = $account[LDAP_ATTR_DESCRIPTION][0];
			#if(isset($account["useraccountcontrol"][0]))
			#	$locked = (intval($account["useraccountcontrol"][0]) & 2) ? 1 : 0;
			/* We currently do not modify the OCO locked flag via LDAP sync.
			   If the user is disabled in AD, then the login function (which tries an LDAP simple bind) will fail anyway. */

			// check group membership and determine role ID
			$groupCheck = null;
			if(empty(LDAP_GROUPS)) {
				$groupCheck = LDAP_DEFAULT_ROLE_ID;
			} else if(isset($account["memberof"])) {
				for($n=0; $n<$account["memberof"]["count"]; $n++) {
					foreach(LDAP_GROUPS as $ldapGroupPath => $roleId) {
						if($account["memberof"][$n] == $ldapGroupPath) {
							if(!in_array($roleId, $availSystemUserRoleIds)) {
								if($this->debug) echo '-> '.$username.': configured role id '.$roleId.' does not exist, skipping !!!'."\n";
								break 2;
							}
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

		// connect to server
		$availDomainUserRoleIds = array_map(function($o){return $o->id;}, $this->db->selectAllDomainUserRole());
		$ldapconn = ldap_connect(LDAP_SERVER);
		if(!$ldapconn) {
			throw new Exception('ldap_connect failed');
		}
		if($this->debug) echo "<=== ldap_connect OK ===>\n";

		// set options and authenticate
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0 );
		$ldapbind = ldap_bind($ldapconn, LDAP_USER.'@'.LDAP_DOMAIN, LDAP_PASS);
		if(!$ldapbind) {
			throw new Exception('ldap_bind failed: '.ldap_error($ldapconn));
		}
		if($this->debug) echo "<=== ldap_bind OK ===>\n";

		// ldap search with paging support
		$data = [];
		$cookie = null;
		do {
			$result = ldap_search(
				$ldapconn, LDAP_QUERY_ROOT, "(objectClass=".ldap_escape(LDAP_USER_CLASS).")",
				[] /*attributes*/, 0 /*attributes_only*/, -1 /*sizelimit*/, -1 /*timelimit*/, LDAP_DEREF_NEVER,
				[ ['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => 750, 'cookie' => $cookie]] ]
			);
			if(!$result) {
				throw new Exception('ldap_search failed: '.ldap_error($ldapconn));
			}
			ldap_parse_result($ldapconn, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);
			$data = array_merge($data, ldap_get_entries($ldapconn, $result));
			if(isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
				$cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
			} else {
				$cookie = null;
			}
		} while(!empty($cookie));
		if($this->debug) echo "<=== ldap_search OK - processing entries... ===>\n";

		// iterate over results array
		$foundDomainUsers = [];
		$foundLdapUsers = [];
		$counter = 1;
		foreach($data as $key => $account) {
			if(!is_numeric($key)) continue; // skip "count" entry
			#var_dump($account); die(); // debug

			if(empty($account[LDAP_ATTR_UID][0])) {
				continue;
			}
			$uid = self::GUIDtoStr($account[LDAP_ATTR_UID][0]);
			if(array_key_exists($uid, $foundLdapUsers)) {
				throw new Exception('Duplicate UID '.$uid.'!');
			}

			// parse LDAP values
			$username    = $account[LDAP_ATTR_USERNAME][0];
			$firstname   = "?";
			$lastname    = "?";
			$displayname = "?";
			$mail        = null;
			$phone       = null;
			$mobile      = null;
			$description = null;
			$locked      = 0;
			if(isset($account[LDAP_ATTR_FIRST_NAME][0]))
				$firstname = $account[LDAP_ATTR_FIRST_NAME][0];
			if(isset($account[LDAP_ATTR_LAST_NAME][0]))
				$lastname = $account[LDAP_ATTR_LAST_NAME][0];
			if(isset($account[LDAP_ATTR_DISPLAY_NAME][0]))
				$displayname = $account[LDAP_ATTR_DISPLAY_NAME][0];
			if(isset($account[LDAP_ATTR_EMAIL][0]))
				$mail = $account[LDAP_ATTR_EMAIL][0];
			if(isset($account[LDAP_ATTR_PHONE][0]))
				$phone = $account[LDAP_ATTR_PHONE][0];
			if(isset($account[LDAP_ATTR_MOBILE][0]))
				$mobile = $account[LDAP_ATTR_MOBILE][0];
			if(isset($account[LDAP_ATTR_DESCRIPTION][0]))
				$description = $account[LDAP_ATTR_DESCRIPTION][0];
			#if(isset($account["useraccountcontrol"][0]))
			#	$locked = (intval($account["useraccountcontrol"][0]) & 2) ? 1 : 0;
			/* We currently do not modify the OCO locked flag via LDAP sync.
			   If the user is disabled in AD, then the login function (which tries an LDAP simple bind) will fail anyway. */

			// check group membership and determine role ID
			$groupCheck = null;
			if(empty(SELF_SERVICE_LDAP_GROUPS)) {
				$groupCheck = SELF_SERVICE_DEFAULT_ROLE_ID;
			} else if(isset($account["memberof"])) {
				for($n=0; $n<$account["memberof"]["count"]; $n++) {
					foreach(SELF_SERVICE_LDAP_GROUPS as $ldapGroupPath => $roleId) {
						if($account["memberof"][$n] == $ldapGroupPath) {
							if(!in_array($roleId, $availDomainUserRoleIds)) {
								if($this->debug) echo '-> '.$username.': configured role id '.$roleId.' does not exist, skipping !!!'."\n";
								break 2;
							}
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
