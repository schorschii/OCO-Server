<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	// ----- create policy object if requested -----
	if(isset($_POST['create_policy_object'])) {
		die($cl->createPolicyObject(
			$_POST['create_policy_object']
		));
	}

	// ----- edit policy object if requested -----
	if(isset($_POST['edit_policy_object_id'])
	&& isset($_POST['name'])) {
		die($cl->editPolicyObject(
			$_POST['edit_policy_object_id'],
			$_POST['name']
		));
	}

	// ----- remove policy object assignment if requested -----
	if(!empty($_POST['remove_policy_object_id'])
	&& is_array($_POST['remove_policy_object_id'])) {
		foreach($_POST['remove_policy_object_id'] as $id) {
			$cl->removePolicyObject($id);
		}
		die();
	}

	// ----- update policy object items if requested -----
	if(!empty($_POST['edit_policy_object_id'])) {
// TODO transaction
// TODO CoreLogic permission check
		$db->deletePolicyObjectItemByPolicyObject($_POST['edit_policy_object_id']);
		foreach($_POST as $key => $value) {
			if(!is_numeric($key)) continue;
			$db->insertPolicyObjectItem($_POST['edit_policy_object_id'], $key, $value, '');
		}
		die();
	}

	// ----- assign policy object if requested -----
	if(
		((!empty($_POST['add_to_computer_group_id']) && is_array($_POST['add_to_computer_group_id']))
		|| (!empty($_POST['add_to_domain_user_group_id']) && is_array($_POST['add_to_domain_user_group_id'])))
	&& !empty($_POST['policy_object_id'])
	&& is_array($_POST['policy_object_id'])) {
// TODO CoreLogic permission check
		foreach($_POST['policy_object_id'] as $policy_object_id) {
			foreach($_POST['add_to_computer_group_id'] ?? [] as $group_id) {
				$db->insertComputerGroupPolicyObject(empty($group_id) ? null : $group_id, $policy_object_id);
			}
			foreach($_POST['add_to_domain_user_group_id'] ?? [] as $group_id) {
				$db->insertDomainUserGroupPolicyObject(empty($group_id) ? null : $group_id, $policy_object_id);
			}
		}
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
