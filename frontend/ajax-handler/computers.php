<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(!empty($_POST['rename_computer_id']) && isset($_POST['new_name'])) {
		$cl->renameComputer($_POST['rename_computer_id'], $_POST['new_name']);
		die();
	}

	if(!empty($_POST['update_computer_id']) && isset($_POST['update_note'])) {
		$cl->updateComputerNote($_POST['update_computer_id'], $_POST['update_note']);
		die();
	}

	if(!empty($_POST['update_computer_id']) && isset($_POST['update_force_update'])) {
		$cl->updateComputerForceUpdate($_POST['update_computer_id'], $_POST['update_force_update']);
		die();
	}

	if(!empty($_POST['wol_id']) && is_array($_POST['wol_id'])) {
		$cl->wolComputers($_POST['wol_id']);
		die();
	}

	if(isset($_POST['create_computer'])) {
		die(strval(intval( $cl->createComputer($_POST['create_computer']) )));
	}

	if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
		foreach($_POST['remove_id'] as $id) {
			$cl->removeComputer($id, !empty($_POST['force']));
		}
		die();
	}

	if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
		foreach($_POST['remove_group_id'] as $id) {
			$cl->removeComputerGroup($id, !empty($_POST['force']));
		}
		die();
	}

	if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_computer_id']) && is_array($_POST['remove_from_group_computer_id'])) {
		foreach($_POST['remove_from_group_computer_id'] as $cid) {
			$cl->removeComputerFromGroup($cid, $_POST['remove_from_group_id']);
		}
		die();
	}

	if(isset($_POST['create_group'])) {
		die(strval(intval(
			$cl->createComputerGroup($_POST['create_group'], empty($_POST['parent_id']) ? null : intval($_POST['parent_id']))
		)));
	}

	if(!empty($_POST['rename_group_id']) && isset($_POST['new_name'])) {
		$cl->renameComputerGroup($_POST['rename_group_id'], $_POST['new_name']);
		die();
	}

	if(isset($_POST['add_to_group_id']) && isset($_POST['add_to_group_computer_id']) && is_array($_POST['add_to_group_computer_id'])) {
		foreach($_POST['add_to_group_computer_id'] as $cid) {
			$cl->addComputerToGroup($cid, $_POST['add_to_group_id']);
		}
		die();
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG['permission_denied']);
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
