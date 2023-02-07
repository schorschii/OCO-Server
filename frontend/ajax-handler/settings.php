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

	if(!empty($_POST['edit_general_config'])) {
		if(isset($_POST['client_api_enabled'])) {
			$cl->editGeneralConfig('client-api-enabled', $_POST['client_api_enabled']);
		}
		if(!empty($_POST['client_api_key'])) { // update only if not empty!
			$cl->editGeneralConfig('client-api-key', $_POST['client_api_key']);
		}
		if(isset($_POST['agent_registration_enabled'])) {
			$cl->editGeneralConfig('agent-self-registration-enabled', $_POST['agent_registration_enabled']);
		}
		if(!empty($_POST['agent_registration_key'])) { // update only if not empty!
			$cl->editGeneralConfig('agent-registration-key', $_POST['agent_registration_key']);
		}
		if(isset($_POST['assume_computer_offline_after'])) {
			$cl->editGeneralConfig('computer-offline-seconds', $_POST['assume_computer_offline_after']);
		}
		if(isset($_POST['wol_shutdown_expiry'])) {
			$cl->editGeneralConfig('wol-shutdown-expiry', $_POST['wol_shutdown_expiry']);
		}
		if(isset($_POST['agent_update_interval'])) {
			$cl->editGeneralConfig('agent-update-interval', $_POST['agent_update_interval']);
		}
		if(isset($_POST['purge_succeeded_jobs_after'])) {
			$cl->editGeneralConfig('purge-succeeded-jobs-after', $_POST['purge_succeeded_jobs_after']);
		}
		if(isset($_POST['purge_failed_jobs_after'])) {
			$cl->editGeneralConfig('purge-failed-jobs-after', $_POST['purge_failed_jobs_after']);
		}
		if(isset($_POST['purge_logs_after'])) {
			$cl->editGeneralConfig('purge-logs-after', $_POST['purge_logs_after']);
		}
		if(isset($_POST['purge_domain_user_logons_after'])) {
			$cl->editGeneralConfig('purge-domain-user-logons-after', $_POST['purge_domain_user_logons_after']);
		}
		if(isset($_POST['purge_events_after'])) {
			$cl->editGeneralConfig('purge-events-after', $_POST['purge_events_after']);
		}
		if(isset($_POST['computer_keep_inactive_screens'])) {
			$cl->editGeneralConfig('computer-keep-inactive-screens', $_POST['computer_keep_inactive_screens']);
		}
		if(isset($_POST['self_service_enabled'])) {
			$cl->editGeneralConfig('self-service-enabled', $_POST['self_service_enabled']);
		}
		die();
	}
	if(!empty($_FILES['edit_license'])) {
		// use file from user upload
		$tmpFilePath = $_FILES['edit_license']['tmp_name'];
		$tmpFileName = $_FILES['edit_license']['name'];
		$cl->editLicense(file_get_contents($tmpFilePath));
		die();
	}

	if(!empty($_POST['edit_system_user_ldap_sync'])) {
		$cl->editSystemUserLdapSync($_POST['edit_system_user_ldap_sync']);
		die();
	}
	if(!empty($_POST['edit_domain_user_ldap_sync'])) {
		$cl->editDomainUserLdapSync($_POST['edit_domain_user_ldap_sync']);
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
