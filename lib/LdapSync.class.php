<?php

class LdapSync {

	private /*DatabaseController*/ $db;
	private /*bool*/ $debug;

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->debug = $debug;
	}

	private static function GUIDtoStr($binary_guid) {
		$unpacked = @unpack('Va/v2b/n2c/Nd', $binary_guid);
		if(!$unpacked) {
			// fallback string representation (base64) if we got unexpected input
			return base64_encode($binary_guid);
		}
		return sprintf('%08x-%04x-%04x-%04x-%04x%08x', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
	}

	private static function applyDefaultLdapAttrs($attrs) {
		$attrs['uid'] = $attrs['uid'] ?? 'objectguid';
		$attrs['username'] = $attrs['username'] ?? 'samaccountname';
		$attrs['first_name'] = $attrs['first_name'] ?? 'givenname';
		$attrs['last_name'] = $attrs['last_name'] ?? 'sn';
		$attrs['display_name'] = $attrs['display_name'] ?? 'displayname';
		$attrs['email'] = $attrs['email'] ?? 'mail';
		$attrs['phone'] = $attrs['phone'] ?? 'telephonenumber';
		$attrs['mobile'] = $attrs['mobile'] ?? 'mobile';
		$attrs['description'] = $attrs['description'] ?? 'description';
		return $attrs;
	}

	public function syncSystemUsers() {
		// get configuration
		$availSystemUserRoleIds = array_map(function($o){return $o->id;}, $this->db->selectAllSystemUserRole());
		$ldapServers = json_decode($this->db->settings->get('system-user-ldapsync'), true);
		if(empty($ldapServers) || !is_array($ldapServers)) {
			throw new Exception('System User LDAP sync not configured!');
		}

		// for each configured server
		$foundLdapUsers = [];
		foreach($ldapServers as $serverIdentifier => $details) {
			$attributes = self::applyDefaultLdapAttrs($details['attribute-matching'] ?? []);
			if(!is_int($serverIdentifier) || intval($serverIdentifier) < 1 || empty($details['address']) || empty($details['username']) || empty($details['password']) || empty($details['query-root']) || empty($details['queries']) || !is_array($details['queries'])) {
				if($this->debug) echo '===> '.($details['address']??$serverIdentifier).': missing configuration values, skipping!'."\n";
				continue;
			}

			// connect to server
			$ldapconn = ldap_connect($details['address']);
			if(!$ldapconn) {
				throw new Exception($details['address'].': ldap_connect failed');
			}
			if($this->debug) echo '===> '.$details['address'].': ldap_connect OK'."\n";

			// set options and authenticate
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
			$ldapbind = ldap_bind($ldapconn, $details['username'], $details['password']);
			if(!$ldapbind) {
				throw new Exception('ldap_bind failed: '.ldap_error($ldapconn));
			}
			if($this->debug) echo '===> ldap_bind OK'."\n";

			foreach($details['queries'] as $query => $roleId) {
				// check role id
				if(!in_array($roleId, $availSystemUserRoleIds)) {
					if($this->debug) echo '-> configured role id '.$roleId.' does not exist, skipping!'."\n";
					break;
				}

				// ldap search with paging support
				$data = [];
				$cookie = null;
				do {
					$result = ldap_search(
						$ldapconn, $details['query-root'], $query,
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
				if($this->debug) echo '===> ldap_search OK - processing entries for role '.$roleId.'...'."\n";

				// iterate over results array
				foreach($data as $key => $account) {
					if(!is_numeric($key)) continue; // skip "count" entry
					#var_dump($account); die(); // debug

					if(empty($account[$attributes['uid']][0])) {
						continue;
					}
					$uid = self::GUIDtoStr($account[$attributes['uid']][0]);
					if(array_key_exists($uid, $foundLdapUsers)) {
						if($this->debug) echo '-> duplicate UID '.$uid.', skipping!'."\n";
						continue;
					}

					// parse LDAP values
					$username    = $account[$attributes['username']][0];
					$firstname   = '?';
					$lastname    = '?';
					$displayname = '?';
					$mail        = null;
					$phone       = null;
					$mobile      = null;
					$description = null;
					$locked      = 0;
					if(isset($account[$attributes['first_name']][0]))
						$firstname = $account[$attributes['first_name']][0];
					if(isset($account[$attributes['last_name']][0]))
						$lastname = $account[$attributes['last_name']][0];
					if(isset($account[$attributes['display_name']][0]))
						$displayname = $account[$attributes['display_name']][0];
					if(isset($account[$attributes['email']][0]))
						$mail = $account[$attributes['email']][0];
					if(isset($account[$attributes['phone']][0]))
						$phone = $account[$attributes['phone']][0];
					if(isset($account[$attributes['mobile']][0]))
						$mobile = $account[$attributes['mobile']][0];
					if(isset($account[$attributes['description']][0]))
						$description = $account[$attributes['description']][0];
					#if(isset($account["useraccountcontrol"][0]))
					#	$locked = (intval($account["useraccountcontrol"][0]) & 2) ? 1 : 0;
					/* We currently do not modify the OCO locked flag via LDAP sync.
					   If the user is disabled in the LDAP directory, then the login function (which tries an LDAP simple bind) will fail anyway. */

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
						if($this->db->updateSystemUser($id, $uid, $username, $displayname, null/*password*/, intval($serverIdentifier), $mail, $phone, $mobile, $description, $locked, $roleId))
							if($this->debug) echo '  OK'."\n";
						else throw new Exception('Error updating: '.$this->db->getLastStatement()->error);
					} else {
						if($this->debug) echo '--> '.$username.': not found in db - creating';

						// insert into db
						if($this->db->insertSystemUser($uid, $username, $displayname, null/*password*/, intval($serverIdentifier), $mail, $phone, $mobile, $description, $locked, $roleId))
							if($this->debug) echo '  OK'."\n";
						else throw new Exception('Error inserting: '.$this->db->getLastStatement()->error);
					}
				}
			}
			ldap_close($ldapconn);
		}

		if($this->debug) echo '===> Check for deleted users...'."\n";
		foreach($this->db->selectAllSystemUser() as $dbUser) {
			if($dbUser->ldap < 1) continue;
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
				if(!empty($details['lock-deleted-users'])) {
					if($this->db->updateSystemUser($dbUser->id, $dbUser->uid, $dbUser->username, $dbUser->display_name, null/*password*/, $dbUser->ldap, $dbUser->email, $dbUser->phone, $dbUser->mobile, $dbUser->description, 1/*locked*/, $dbUser->system_user_role_id)) {
						if($this->debug) echo '--> '.$dbUser->username.': locking  OK'."\n";
					}
					else throw new Exception('Error locking '.$dbUser->username.': '.$this->db->getLastStatement()->error);
				} else {
					if($this->db->deleteSystemUser($dbUser->id)) {
						if($this->debug) echo '--> '.$dbUser->username.': deleting  OK'."\n";
					}
					else throw new Exception('Error deleting '.$dbUser->username.': '.$this->db->getLastStatement()->error);
				}
			}
		}
	}

	public function syncDomainUsers() {
		if(!$this->db->settings->get('self-service-enabled')) {
			echo LANG('self_service_is_disabled')."\n";
			return;
		}

		// get configuration
		$availDomainUserRoleIds = array_map(function($o){return $o->id;}, $this->db->selectAllDomainUserRole());
		$ldapServers = json_decode($this->db->settings->get('domain-user-ldapsync'), true);
		if(empty($ldapServers) || !is_array($ldapServers)) {
			throw new Exception('Domain User LDAP sync not configured!');
		}

		// for each configured server
		$foundDomainUsers = [];
		$foundLdapUsers = [];
		foreach($ldapServers as $serverIdentifier => $details) {
			$attributes = self::applyDefaultLdapAttrs($details['attribute-matching'] ?? []);
			if(!is_int($serverIdentifier) || intval($serverIdentifier) < 1 || empty($details['address']) || empty($details['username']) || empty($details['password']) || empty($details['query-root']) || empty($details['queries']) || !is_array($details['queries'])) {
				if($this->debug) echo "===> ".($details['address']??$serverIdentifier).": missing configuration values, skipping!\n";
				continue;
			}

			// connect to server
			$ldapconn = ldap_connect($details['address']);
			if(!$ldapconn) {
				throw new Exception($details['address'].': ldap_connect failed');
			}
			if($this->debug) echo '===> '.$details['address'].': ldap_connect OK'."\n";

			// set options and authenticate
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
			$ldapbind = ldap_bind($ldapconn, $details['username'], $details['password']);
			if(!$ldapbind) {
				throw new Exception('ldap_bind failed: '.ldap_error($ldapconn));
			}
			if($this->debug) echo '===> ldap_bind OK'."\n";

			foreach($details['queries'] as $query => $roleId) {
				// check role id
				if(!in_array($roleId, $availDomainUserRoleIds)) {
					if($this->debug) echo '-> configured role id '.$roleId.' does not exist, skipping!'."\n";
					break 2;
				}

				// ldap search with paging support
				$data = [];
				$cookie = null;
				do {
					$result = ldap_search(
						$ldapconn, $details['query-root'], $query,
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
				if($this->debug) echo '===> ldap_search OK - processing entries for role '.$roleId.'...'."\n";

				// iterate over results array
				foreach($data as $key => $account) {
					if(!is_numeric($key)) continue; // skip "count" entry
					#var_dump($account); die(); // debug

					if(empty($account[$attributes['uid']][0])) {
						continue;
					}
					$uid = self::GUIDtoStr($account[$attributes['uid']][0]);
					if(array_key_exists($uid, $foundLdapUsers)) {
						if($this->debug) echo '-> duplicate UID '.$uid.', skipping!'."\n";
						continue;
					}

					// parse LDAP values
					$username    = $account[$attributes['username']][0];
					$firstname   = '?';
					$lastname    = '?';
					$displayname = '?';
					$mail        = null;
					$phone       = null;
					$mobile      = null;
					$description = null;
					$locked      = 0;
					if(isset($account[$attributes['first_name']][0]))
						$firstname = $account[$attributes['first_name']][0];
					if(isset($account[$attributes['last_name']][0]))
						$lastname = $account[$attributes['last_name']][0];
					if(isset($account[$attributes['display_name']][0]))
						$displayname = $account[$attributes['display_name']][0];
					if(isset($account[$attributes['email']][0]))
						$mail = $account[$attributes['email']][0];
					if(isset($account[$attributes['phone']][0]))
						$phone = $account[$attributes['phone']][0];
					if(isset($account[$attributes['mobile']][0]))
						$mobile = $account[$attributes['mobile']][0];
					if(isset($account[$attributes['description']][0]))
						$description = $account[$attributes['description']][0];
					#if(isset($account["useraccountcontrol"][0]))
					#	$locked = (intval($account["useraccountcontrol"][0]) & 2) ? 1 : 0;
					/* We currently do not modify the OCO locked flag via LDAP sync.
					   If the user is disabled in the LDAP directory, then the login function (which tries an LDAP simple bind) will fail anyway. */

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
						if($this->db->updateDomainUser($id, $roleId, null, intval($serverIdentifier)))
							if($this->debug) echo '  OK'."\n";
						else throw new Exception('Error updating: '.$this->db->getLastStatement()->error);
					}
				}
			}
			ldap_close($ldapconn);
		}

		// set ldap=0 to all not found entries
		if($this->debug) echo '===> revoke access for removed LDAP domain users';
		if($this->db->revokeAllLdapDomainUserByIds($foundDomainUsers))
			if($this->debug) echo "  OK\n";
		else throw new Exception('Error updating: '.$this->db->getLastStatement()->error);
	}

}
