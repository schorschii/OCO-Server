<?php
require_once(__DIR__.'/Loader.php');


if(LDAP_SERVER == null) {
	die("<===== LDAP Sync Not Configured! =====>\n");
}

$ldapconn = ldap_connect(LDAP_SERVER);
if(!$ldapconn) {
	die("<===== ldap_connect FAILED =====>\n");
}

echo "<===== ldap_connect OK =====>\n";
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0 );
$ldapbind = ldap_bind($ldapconn, LDAP_USER.'@'.LDAP_DOMAIN, LDAP_PASS);
if(!$ldapbind) {
	die("<===== ldap_bind FAILED: ".ldap_error($ldapconn)." =====>\n");
}

echo "<===== ldap_bind OK =====>\n";
$result = ldap_search($ldapconn, LDAP_QUERY_ROOT, "(objectClass=".ldap_escape(LDAP_USER_CLASS).")");
if(!$result) {
	die("<===== ldap_search FAILED: ".ldap_error($ldapconn)." =====>\n");
}

$data = ldap_get_entries($ldapconn, $result);

echo "<===== ldap_search OK - processing ".$data["count"]." entries... =====>\n";

// iterate over result array
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
		echo "-> ".$username.": skip because not in required group\n";
		continue;
	}

	// add to found array
	$foundLdapUsers[$uid] = $username;

	// check if user already exists
	$id = null;
	$checkResult = $db->getSystemUserByUid($uid);
	if(empty($checkResult)) {
		// fallback for old DB schema without uid
		$tmpCheckResult = $db->getSystemUserByLogin($username);
		if(!empty($tmpCheckResult) && empty($tmpCheckResult->uid)) {
			$checkResult = $tmpCheckResult;
		}
	}
	if($checkResult !== null) {
		$id = $checkResult->id;
		echo "--> ".$username.": found in db - update id: ".$id;

		// update into db
		if($db->updateSystemUser($id, $uid, $username, $displayname, null/*password*/, 1/*ldap-flag*/, $mail, $phone, $mobile, $description, 0/*locked*/, $groupCheck))
			echo "  OK\n";
		else echo "  ERROR: ".$db->getLastStatement()->error."\n";
	} else {
		echo "--> ".$username.": not found in db - creating";

		// insert into db
		if($db->addSystemUser($uid, $username, $displayname, null/*password*/, 1/*ldap-flag*/, $mail, $phone, $mobile, $description, 0/*locked*/, $groupCheck))
			echo "  OK\n";
		else echo "  ERROR: ".$db->getLastStatement()->error."\n";
	}
	$counter ++;
}
ldap_close($ldapconn);

echo "<===== Check For Deleted Users... =====>\n";
foreach($db->getAllSystemUser() as $dbUser) {
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
		if($db->removeSystemUser($dbUser->id)) echo "--> ".$dbUser->username.": deleting  OK\n";
		else echo "--> ".$dbUser->username.": deleting  ERROR: ".$db->getLastStatement()->error."\n";
	}
}
