<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

try {

	if(!empty($_POST['ldap_sync_system_users'])) {
		try {
			$ldapSync = new LdapSync($db, true);
			$ldapSync->syncSystemUsers();
		} catch(Exception $e) {
			header('HTTP/1.1 500 Internal Server Error');
			die($e->getMessage());
		}
		die();
	}

	if(!empty($_POST['ldap_sync_domain_users'])) {
		try {
			$ldapSync = new LdapSync($db, true);
			$ldapSync->syncDomainUsers();
		} catch(Exception $e) {
			header('HTTP/1.1 500 Internal Server Error');
			die($e->getMessage());
		}
		die();
	}

	if(!empty($_POST['edit_system_user_id'])
	&& isset($_POST['username'])
	&& isset($_POST['display_name'])
	&& isset($_POST['description'])
	&& isset($_POST['password'])
	&& isset($_POST['system_user_role_id'])) {
		if($_POST['edit_system_user_id'] == '-1') {
			die($cl->createSystemUser(
				$_POST['username'],
				$_POST['display_name'],
				$_POST['description'],
				$_POST['password'],
				$_POST['system_user_role_id']
			));
		} else {
			$cl->editSystemUser(
				$_POST['edit_system_user_id'],
				$_POST['username'],
				$_POST['display_name'],
				$_POST['description'],
				$_POST['password'],
				$_POST['system_user_role_id']
			);
		}
		die();
	}

	if(!empty($_POST['edit_own_system_user_password'])
	&& isset($_POST['old_password'])) {
		$cl->editOwnSystemUserPassword(
			$_POST['old_password'], $_POST['edit_own_system_user_password']
		);
		die();
	}

	if(!empty($_POST['remove_system_user_id'])
	&& is_array($_POST['remove_system_user_id'])) {
		foreach($_POST['remove_system_user_id'] as $id) {
			$cl->removeSystemUser($id);
		}
		die();
	}

	if(!empty($_POST['lock_system_user_id'])
	&& is_array($_POST['lock_system_user_id'])) {
		foreach($_POST['lock_system_user_id'] as $id) {
			$cl->editSystemUserLocked($id, 1);
		}
		die();
	}

	if(!empty($_POST['unlock_system_user_id'])
	&& is_array($_POST['unlock_system_user_id'])) {
		foreach($_POST['unlock_system_user_id'] as $id) {
			$cl->editSystemUserLocked($id, 0);
		}
		die();
	}

	if(!empty($_POST['edit_system_user_role_id'])
	&& isset($_POST['name'])
	&& isset($_POST['permissions'])) {
		if($_POST['edit_system_user_role_id'] == '-1') {
			die($cl->createSystemUserRole(
				$_POST['name'],
				$_POST['permissions']
			));
		} else {
			$cl->editSystemUserRole($_POST['edit_system_user_role_id'],
				$_POST['name'],
				$_POST['permissions']
			);
		}
		die();
	}

	if(!empty($_POST['remove_system_user_role_id']) && is_array($_POST['remove_system_user_role_id'])) {
		foreach($_POST['remove_system_user_role_id'] as $id) {
			$cl->removeSystemUserRole($id);
		}
		die();
	}

	if(!empty($_POST['edit_domain_user_id'])
	&& isset($_POST['password'])
	&& isset($_POST['domain_user_role_id'])) {
		$cl->editDomainUser(
			$_POST['edit_domain_user_id'],
			$_POST['password'],
			$_POST['domain_user_role_id']
		);
		die();
	}

	if(!empty($_POST['edit_domain_user_role_id'])
	&& isset($_POST['name'])
	&& isset($_POST['permissions'])) {
		if($_POST['edit_domain_user_role_id'] == '-1') {
			die($cl->createDomainUserRole(
				$_POST['name'],
				$_POST['permissions']
			));
		} else {
			$cl->editDomainUserRole($_POST['edit_domain_user_role_id'],
				$_POST['name'],
				$_POST['permissions']
			);
		}
		die();
	}

	if(!empty($_POST['remove_domain_user_role_id']) && is_array($_POST['remove_domain_user_role_id'])) {
		foreach($_POST['remove_domain_user_role_id'] as $id) {
			$cl->removeDomainUserRole($id);
		}
		die();
	}

	if(!empty($_POST['edit_event_query_rule_id'])
	&& isset($_POST['log'])
	&& isset($_POST['query'])) {
		if($_POST['edit_event_query_rule_id'] == '-1') {
			die($cl->createEventQueryRule(
				$_POST['log'],
				$_POST['query']
			));
		} else {
			$cl->editEventQueryRule(
				$_POST['edit_event_query_rule_id'],
				$_POST['log'],
				$_POST['query']
			);
		}
		die();
	}

	if(!empty($_POST['remove_event_query_rule_id']) && is_array($_POST['remove_event_query_rule_id'])) {
		foreach($_POST['remove_event_query_rule_id'] as $id) {
			$cl->removeEventQueryRule($id);
		}
		die();
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
