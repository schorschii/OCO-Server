<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(!empty($_POST['rename_computer_id']) && isset($_POST['new_name'])) {
		if(empty(trim($_POST['new_name']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->updateComputerHostname($_POST['rename_computer_id'], $_POST['new_name']);
		die();
	}

	if(!empty($_POST['update_computer_id']) && isset($_POST['update_note'])) {
		$db->updateComputerNote($_POST['update_computer_id'], $_POST['update_note']);
		die();
	}

	if(!empty($_POST['update_computer_id']) && isset($_POST['update_force_update'])) {
		$db->updateComputerForceUpdate($_POST['update_computer_id'], $_POST['update_force_update']);
		die();
	}

	if(!empty($_POST['remove_package_assignment_id']) && is_array($_POST['remove_package_assignment_id'])) {
		foreach($_POST['remove_package_assignment_id'] as $id) {
			$cl->removeComputerAssignedPackage($id);
		}
		die();
	}

	if(!empty($_POST['uninstall_package_assignment_id']) && is_array($_POST['uninstall_package_assignment_id'])) {
		$name = ''; // compile name
		foreach($_POST['uninstall_package_assignment_id'] as $id) {
			$ap = $db->getComputerAssignedPackage($id);
			if(empty($ap)) continue;
			$c = $db->getComputer($ap->computer_id);
			if(empty($name)) $name = LANG['uninstall'].' '.$c->hostname;
			else $name .= ', '.$c->hostname;
		}
		if(empty($name)) $name = LANG['uninstall'];
		$cl->uninstall(
			$name, '', $_SESSION['um_username'],
			$_POST['uninstall_package_assignment_id'], $_POST['start_time'] ?? date('Y-m-d H:i:s'), null,
			1/*use wol*/, 0/*shutdown waked after completion*/, 5/*restart timeout*/
		);
		die();
	}

	if(!empty($_POST['wol_id']) && is_array($_POST['wol_id'])) {
		$cl->wolComputers($_POST['wol_id']);
		die();
	}

	if(isset($_POST['add_computer'])) {
		if(empty(trim($_POST['add_computer']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		die(strval(intval( $cl->createComputer($_POST['add_computer']) )));
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
			$db->removeComputerFromGroup($cid, $_POST['remove_from_group_id']);
		}
		die();
	}

	if(isset($_POST['add_group'])) {
		if(empty(trim($_POST['add_group']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$insertId = -1;
		if(empty($_POST['parent_id'])) $insertId = $db->addComputerGroup($_POST['add_group']);
		else $insertId = $db->addComputerGroup($_POST['add_group'], intval($_POST['parent_id']));
		die(strval(intval($insertId)));
	}

	if(!empty($_POST['rename_group']) && isset($_POST['new_name'])) {
		if(empty(trim($_POST['new_name']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->renameComputerGroup($_POST['rename_group'], $_POST['new_name']);
		die();
	}

	if(isset($_POST['add_to_group_id']) && isset($_POST['add_to_group_computer_id']) && is_array($_POST['add_to_group_computer_id'])) {
		if($db->getComputerGroup($_POST['add_to_group_id']) == null) {
			throw new Exception(LANG['not_found']);
		}
		foreach($_POST['add_to_group_computer_id'] as $cid) {
			if(count($db->getComputerByComputerAndGroup($cid, $_POST['add_to_group_id'])) == 0) {
				$db->addComputerToGroup($cid, $_POST['add_to_group_id']);
			}
		}
		die();
	}

} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
