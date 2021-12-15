<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(!empty($_POST['update_package_family_id']) && isset($_POST['update_name'])) {
		if(empty(trim($_POST['update_name']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->updatePackageFamilyName($_POST['update_package_family_id'], $_POST['update_name']);
		die();
	}

	if(!empty($_POST['update_package_family_id']) && isset($_POST['update_notes'])) {
		$db->updatePackageFamilyNotes($_POST['update_package_family_id'], $_POST['update_notes']);
		die();
	}

	if(!empty($_POST['update_package_family_id']) && !empty($_FILES['update_icon']['tmp_name'])) {
		if(!exif_imagetype($_FILES['update_icon']['tmp_name'])) {
			throw new Exception(LANG['invalid_input']);
		}
		$db->updatePackageFamilyIcon($_POST['update_package_family_id'], file_get_contents($_FILES['update_icon']['tmp_name']));
		die();
	}

	if(!empty($_POST['update_package_family_id']) && !empty($_POST['remove_icon'])) {
		$db->updatePackageFamilyIcon($_POST['update_package_family_id'], null);
		die();
	}

	if(!empty($_POST['update_package_id']) && !empty($_POST['add_dependency_package_id'])) {
		$db->addPackageDependency($_POST['update_package_id'], $_POST['add_dependency_package_id']);
		die();
	}

	if(!empty($_POST['update_package_id']) && !empty($_POST['remove_dependency_package_id']) && is_array($_POST['remove_dependency_package_id'])) {
		foreach($_POST['remove_dependency_package_id'] as $dpid) {
			$db->removePackageDependency($_POST['update_package_id'], $dpid);
		}
		die();
	}

	if(!empty($_POST['update_package_id']) && !empty($_POST['remove_dependent_package_id']) && is_array($_POST['remove_dependent_package_id'])) {
		foreach($_POST['remove_dependent_package_id'] as $dpid) {
			$db->removePackageDependency($dpid, $_POST['update_package_id']);
		}
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_version'])) {
		if(empty(trim($_POST['update_version']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->updatePackageVersion($_POST['update_package_id'], $_POST['update_version']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_note'])) {
		$db->updatePackageNote($_POST['update_package_id'], $_POST['update_note']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure'])) {
		if(empty(trim($_POST['update_install_procedure']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->updatePackageInstallProcedure($_POST['update_package_id'], $_POST['update_install_procedure']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure_success_return_codes'])) {
		$db->updatePackageInstallProcedureSuccessReturnCodes($_POST['update_package_id'], $_POST['update_install_procedure_success_return_codes']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure_action'])) {
		if(is_numeric($_POST['update_install_procedure_action'])
		&& in_array($_POST['update_install_procedure_action'], [Package::POST_ACTION_NONE, Package::POST_ACTION_RESTART, Package::POST_ACTION_SHUTDOWN, Package::POST_ACTION_EXIT])) {
			$db->updatePackageInstallProcedurePostAction($_POST['update_package_id'], $_POST['update_install_procedure_action']);
		} else {
			throw new Exception(LANG['invalid_input']);
		}
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure'])) {
		$db->updatePackageUninstallProcedure($_POST['update_package_id'], $_POST['update_uninstall_procedure']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure_success_return_codes'])) {
		$db->updatePackageUninstallProcedureSuccessReturnCodes($_POST['update_package_id'], $_POST['update_uninstall_procedure_success_return_codes']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure_action'])) {
		if(is_numeric($_POST['update_uninstall_procedure_action'])
		&& in_array($_POST['update_uninstall_procedure_action'], [Package::POST_ACTION_NONE, Package::POST_ACTION_RESTART, Package::POST_ACTION_SHUTDOWN])) {
			$db->updatePackageUninstallProcedurePostAction($_POST['update_package_id'], $_POST['update_uninstall_procedure_action']);
		} else {
			throw new Exception(LANG['invalid_input']);
		}
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_download_for_uninstall'])) {
		if($_POST['update_download_for_uninstall'] == '0') {
			$db->updatePackageDownloadForUninstall($_POST['update_package_id'], 0);
		} elseif($_POST['update_download_for_uninstall'] == '1') {
			$db->updatePackageDownloadForUninstall($_POST['update_package_id'], 1);
		} else {
			throw new Exception(LANG['invalid_input']);
		}
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_compatible_os'])) {
		$db->updatePackageCompatibleOs($_POST['update_package_id'], $_POST['update_compatible_os']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_compatible_os_version'])) {
		$db->updatePackageCompatibleOsVersion($_POST['update_package_id'], $_POST['update_compatible_os_version']);
		die();
	}

	if(!empty($_POST['remove_package_family_id']) && is_array($_POST['remove_package_family_id'])) {
		foreach($_POST['remove_package_family_id'] as $id) {
			$cl->removePackageFamily($id);
		}
		die();
	}

	if(!empty($_POST['move_in_group']) && !empty($_POST['move_from_pos']) && !empty($_POST['move_to_pos'])) {
		$db->reorderPackageInGroup($_POST['move_in_group'], $_POST['move_from_pos'], $_POST['move_to_pos']);
		die();
	}

	if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
		foreach($_POST['remove_id'] as $id) {
			$cl->removePackage($id, !empty($_POST['force']));
		}
		die();
	}

	if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
		foreach($_POST['remove_group_id'] as $id) {
			$cl->removePackageGroup($id, !empty($_POST['force']));
		}
		die();
	}

	if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_package_id']) && is_array($_POST['remove_from_group_package_id'])) {
		foreach($_POST['remove_from_group_package_id'] as $pid) {
			$db->removePackageFromGroup($pid, $_POST['remove_from_group_id']);
		}
		die();
	}

	if(isset($_POST['create_group'])) {
		if(empty(trim($_POST['create_group']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$insertId = -1;
		if(empty($_POST['parent_id'])) $insertId = $db->addPackageGroup($_POST['create_group']);
		else $insertId = $db->addPackageGroup($_POST['create_group'], intval($_POST['parent_id']));
		die(strval(intval($insertId)));
	}

	if(!empty($_POST['rename_group']) && isset($_POST['new_name'])) {
		if(empty(trim($_POST['new_name']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->renamePackageGroup($_POST['rename_group'], $_POST['new_name']);
		die();
	}

	if(isset($_POST['add_to_group_id']) && isset($_POST['add_to_group_package_id']) && is_array($_POST['add_to_group_package_id'])) {
		if($db->getPackageGroup($_POST['add_to_group_id']) == null) {
			throw new Exception(LANG['not_found']);
		}
		foreach($_POST['add_to_group_package_id'] as $pid) {
			if(count($db->getPackageByPackageAndGroup($pid, $_POST['add_to_group_id'])) == 0) {
				$db->addPackageToGroup($pid, $_POST['add_to_group_id']);
			}
		}
		die();
	}

	if(isset($_POST['create_package'])) {
		// no payload by default
		$tmpFilePath = null;
		$tmpFileName = null;
		if(!empty($_FILES['archive'])) {
			// use file from user upload
			$tmpFilePath = $_FILES['archive']['tmp_name'];
			$tmpFileName = $_FILES['archive']['name'];
		}
		// create package
		$insertId = $cl->createPackage($_POST['create_package'], $_POST['version'], $_POST['description'] ?? '', $_SESSION['oco_username'] ?? '',
			$_POST['install_procedure'], $_POST['install_procedure_success_return_codes'] ?? '', $_POST['install_procedure_post_action'] ?? null,
			$_POST['uninstall_procedure'] ?? '', $_POST['uninstall_procedure_success_return_codes'] ?? '', $_POST['download_for_uninstall'], $_POST['uninstall_procedure_post_action'] ?? null,
			$_POST['compatible_os'] ?? null, $_POST['compatible_os_version'] ?? null, $tmpFilePath, $tmpFileName
		);
		die(strval(intval($insertId)));
	}

} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
