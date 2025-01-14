<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	if(!empty($_POST['ldap_sync_system_users'])) {
		$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
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
		$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
		try {
			$ldapSync = new LdapSync($db, true);
			$ldapSync->syncDomainUsers();
		} catch(Exception $e) {
			header('HTTP/1.1 500 Internal Server Error');
			die($e->getMessage());
		}
		die();
	}

	if(!empty($_POST['sync_apple_devices'])) {
		$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
		try {
			$ade = new Apple\AutomatedDeviceEnrollment($db);
			$ade->syncDevices();
		} catch(Exception $e) {
			header('HTTP/1.1 500 Internal Server Error');
			die($e->getMessage());
		}
		die();
	}

	if(!empty($_POST['sync_apple_assets'])) {
		$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
		try {
			$ade = new Apple\VolumePurchaseProgram($db);
			$ade->syncAssets();
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

	if(!empty($_POST['edit_password_rotation_rule_id'])
	&& isset($_POST['computer_group_id'])
	&& isset($_POST['username'])
	&& isset($_POST['alphabet'])
	&& isset($_POST['length'])
	&& isset($_POST['valid_seconds'])
	&& isset($_POST['history'])) {
		die($cl->createEditPasswordRotationRule(
			$_POST['edit_password_rotation_rule_id']<=0 ? null : $_POST['edit_password_rotation_rule_id'],
			$_POST['computer_group_id'] ? $_POST['computer_group_id'] : null,
			$_POST['username'],
			$_POST['alphabet'],
			$_POST['length'],
			$_POST['valid_seconds'],
			$_POST['history'],
		));
	}

	if(!empty($_POST['remove_password_rotation_rule_id']) && is_array($_POST['remove_password_rotation_rule_id'])) {
		foreach($_POST['remove_password_rotation_rule_id'] as $id) {
			$cl->removePasswordRotationRule($id);
		}
		die();
	}

	if(!empty($_POST['edit_general_config'])) {
		if(isset($_POST['client_api_enabled'])) {
			$cl->editSetting('client-api-enabled', $_POST['client_api_enabled']);
		}
		if(!empty($_POST['client_api_key'])) { // update only if not empty!
			$cl->editSetting('client-api-key', $_POST['client_api_key']);
		}
		if(isset($_POST['agent_registration_enabled'])) {
			$cl->editSetting('agent-self-registration-enabled', $_POST['agent_registration_enabled']);
		}
		if(!empty($_POST['agent_registration_key'])) { // update only if not empty!
			$cl->editSetting('agent-registration-key', $_POST['agent_registration_key']);
		}
		if(isset($_POST['assume_computer_offline_after'])) {
			$cl->editSetting('computer-offline-seconds', $_POST['assume_computer_offline_after']);
		}
		if(isset($_POST['wol_shutdown_expiry'])) {
			$cl->editSetting('wol-shutdown-expiry', $_POST['wol_shutdown_expiry']);
		}
		if(isset($_POST['agent_update_interval'])) {
			$cl->editSetting('agent-update-interval', $_POST['agent_update_interval']);
		}
		if(isset($_POST['purge_succeeded_jobs_after'])) {
			$cl->editSetting('purge-succeeded-jobs-after', $_POST['purge_succeeded_jobs_after']);
		}
		if(isset($_POST['purge_failed_jobs_after'])) {
			$cl->editSetting('purge-failed-jobs-after', $_POST['purge_failed_jobs_after']);
		}
		if(isset($_POST['purge_domain_user_logons_after'])) {
			$cl->editSetting('purge-domain-user-logons-after', $_POST['purge_domain_user_logons_after']);
		}
		if(isset($_POST['purge_events_after'])) {
			$cl->editSetting('purge-events-after', $_POST['purge_events_after']);
		}
		if(isset($_POST['log_level'])) {
			$cl->editSetting('log-level', $_POST['log_level']);
		}
		if(isset($_POST['purge_logs_after'])) {
			$cl->editSetting('purge-logs-after', $_POST['purge_logs_after']);
		}
		if(isset($_POST['computer_keep_inactive_screens'])) {
			$cl->editSetting('computer-keep-inactive-screens', $_POST['computer_keep_inactive_screens']);
		}
		if(isset($_POST['self_service_enabled'])) {
			$cl->editSetting('self-service-enabled', $_POST['self_service_enabled']);
		}
		die();
	}
	if(!empty($_POST['edit_wol_satellites'])) {
		$cl->editWolSatellites($_POST['edit_wol_satellites']);
		die();
	}
	if(!empty($_POST['edit_setting'])) {
		$key = $_POST['edit_setting'];
		if(isset($_POST['value'])) {
			// special handlings
			if($key == 'apple-mdm-activation-profile') {
				$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
				$ade = new Apple\AutomatedDeviceEnrollment($db);
				$ade->storeActivationProfile($_POST['value']);
				die();
			}
			if($key == 'apple-appstore-teamid') {
				$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
				$vpp = new Apple\VolumePurchaseProgram($db);
				$as = new Apple\AppStore($db, $vpp);
				$as->storeTeamId($_POST['value']);
				die();
			}
			if($key == 'apple-appstore-keyid') {
				$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
				$vpp = new Apple\VolumePurchaseProgram($db);
				$as = new Apple\AppStore($db, $vpp);
				$as->storeKeyId($_POST['value']);
				die();
			}
			$cl->editSetting($key, $_POST['value']);
			die();
		} elseif(isset($_FILES['value'])) {
			// special handlings
			$value = file_get_contents($_FILES['value']['tmp_name']);
			if($key == 'apple-mdm-token') {
				$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
				$ade = new Apple\AutomatedDeviceEnrollment($db);
				$ade->storeMdmServerToken($value);
				die();
			}
			if($key == 'apple-vpp-token') {
				$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
				$vpp = new Apple\VolumePurchaseProgram($db);
				$vpp->storeToken($value);
				die();
			}
			if($key == 'apple-appstore-key') {
				$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
				$vpp = new Apple\VolumePurchaseProgram($db);
				$as = new Apple\AppStore($db, $vpp);
				$as->storeKey($value);
				die();
			}
			if($key == 'apple-mdm-vendor-cert') { // ('apple-mdm-apn-cert' file is already pem encoded)
				$value = Apple\Util\PemDerConverter::der2pem($value);
			}
			$cl->editSetting($key, $value);
			die();
		}
	}
	if(!empty($_POST['remove_setting']) && is_array($_POST['remove_setting'])) {
		foreach($_POST['remove_setting'] as $setting) {
			$cl->removeSetting($setting);
		}
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
	die(htmlspecialchars($e->getMessage()));
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
