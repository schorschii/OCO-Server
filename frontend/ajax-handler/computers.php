<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	if(!empty($_POST['get_computer_names']) && is_array($_POST['get_computer_names'])) {
		$finalArray = [];
		foreach($_POST['get_computer_names'] as $id) {
			$c = $cl->getComputer($id);
			if(!empty($c)) $finalArray[] = ['id'=>$c->id, 'name'=>$c->hostname];
		}
		die(json_encode($finalArray));
	}

	if(!empty($_POST['edit_computer_id'])
	&& isset($_POST['hostname'])
	&& isset($_POST['notes'])) {
		$cl->editComputer($_POST['edit_computer_id'], $_POST['hostname'], $_POST['notes']);
		die();
	}

	if(!empty($_POST['edit_computer_id'])
	&& isset($_POST['force_update'])) {
		$cl->editComputerForceUpdate($_POST['edit_computer_id'], $_POST['force_update']);
		die();
	}

	if(!empty($_POST['wol_id']) && is_array($_POST['wol_id'])) {
		$cl->wolComputers($_POST['wol_id']);
		die();
	}

	if(isset($_POST['create_computer'])) {
		die(
			$cl->createComputer(
				$_POST['create_computer'], $_POST['notes']??'',
				$_POST['agent_key']??'', $_POST['server_key']??''
			)
		);
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
		die(
			$cl->createComputerGroup($_POST['create_group'], empty($_POST['parent_id']) ? null : intval($_POST['parent_id']))
		);
	}

	if(!empty($_POST['rename_group_id']) && isset($_POST['new_name'])) {
		$cl->renameComputerGroup($_POST['rename_group_id'], $_POST['new_name']);
		die();
	}

	if(isset($_POST['add_to_group_id']) && is_array($_POST['add_to_group_id']) && isset($_POST['add_to_group_computer_id']) && is_array($_POST['add_to_group_computer_id'])) {
		foreach($_POST['add_to_group_computer_id'] as $cid) {
			foreach($_POST['add_to_group_id'] as $gid) {
				$cl->addComputerToGroup($cid, $gid);
			}
		}
		die();
	}

	if(!empty($_POST['edit_computer_id'])
	&& !empty($_POST['add_package_id']) && is_array($_POST['add_package_id'])) {
		foreach($_POST['add_package_id'] as $p)
			$cl->addComputerPackage($_POST['edit_computer_id'], $p);
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
