<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(!empty($_POST['update_package_family_id']) && isset($_POST['update_name'])) {
		$cl->renamePackageFamily($_POST['update_package_family_id'], $_POST['update_name']);
		die();
	}

	if(!empty($_POST['update_package_family_id']) && isset($_POST['update_notes'])) {
		$cl->updatePackageFamilyNotes($_POST['update_package_family_id'], $_POST['update_notes']);
		die();
	}

	if(!empty($_POST['update_package_family_id']) && !empty($_FILES['update_icon']['tmp_name'])) {
		if(!exif_imagetype($_FILES['update_icon']['tmp_name'])) {
			throw new Exception(LANG['invalid_input']);
		}
		$cl->updatePackageFamilyIcon($_POST['update_package_family_id'], file_get_contents($_FILES['update_icon']['tmp_name']));
		die();
	}

	if(!empty($_POST['update_package_family_id']) && !empty($_POST['remove_icon'])) {
		$cl->updatePackageFamilyIcon($_POST['update_package_family_id'], null);
		die();
	}

	if(!empty($_POST['update_package_id']) && !empty($_POST['add_dependency_package_id'])) {
		$cl->addPackageDependency($_POST['update_package_id'], $_POST['add_dependency_package_id']);
		die();
	}

	if(!empty($_POST['update_package_id']) && !empty($_POST['remove_dependency_package_id']) && is_array($_POST['remove_dependency_package_id'])) {
		foreach($_POST['remove_dependency_package_id'] as $dpid) {
			$cl->removePackageDependency($_POST['update_package_id'], $dpid);
		}
		die();
	}

	if(!empty($_POST['update_package_id']) && !empty($_POST['remove_dependent_package_id']) && is_array($_POST['remove_dependent_package_id'])) {
		foreach($_POST['remove_dependent_package_id'] as $dpid) {
			$cl->removePackageDependency($dpid, $_POST['update_package_id']);
		}
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_version'])) {
		$cl->updatePackageVersion($_POST['update_package_id'], $_POST['update_version']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_note'])) {
		$cl->updatePackageNote($_POST['update_package_id'], $_POST['update_note']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure'])) {
		$cl->updatePackageInstallProcedure($_POST['update_package_id'], $_POST['update_install_procedure']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure_success_return_codes'])) {
		$cl->updatePackageInstallProcedureSuccessReturnCodes($_POST['update_package_id'], $_POST['update_install_procedure_success_return_codes']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure_action'])) {
		$cl->updatePackageInstallProcedurePostAction($_POST['update_package_id'], $_POST['update_install_procedure_action']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure'])) {
		$cl->updatePackageUninstallProcedure($_POST['update_package_id'], $_POST['update_uninstall_procedure']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure_success_return_codes'])) {
		$cl->updatePackageUninstallProcedureSuccessReturnCodes($_POST['update_package_id'], $_POST['update_uninstall_procedure_success_return_codes']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure_action'])) {
		$cl->updatePackageUninstallProcedurePostAction($_POST['update_package_id'], $_POST['update_uninstall_procedure_action']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_download_for_uninstall'])) {
		$cl->updatePackageDownloadForUninstall($_POST['update_package_id'], $_POST['update_download_for_uninstall']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_compatible_os'])) {
		$cl->updatePackageCompatibleOs($_POST['update_package_id'], $_POST['update_compatible_os']);
		die();
	}

	if(!empty($_POST['update_package_id']) && isset($_POST['update_compatible_os_version'])) {
		$cl->updatePackageCompatibleOsVersion($_POST['update_package_id'], $_POST['update_compatible_os_version']);
		die();
	}

	if(!empty($_POST['remove_package_family_id']) && is_array($_POST['remove_package_family_id'])) {
		foreach($_POST['remove_package_family_id'] as $id) {
			$cl->removePackageFamily($id);
		}
		die();
	}

	if(!empty($_POST['move_in_group_id']) && !empty($_POST['move_from_pos']) && !empty($_POST['move_to_pos'])) {
		$cl->reorderPackageInGroup($_POST['move_in_group_id'], $_POST['move_from_pos'], $_POST['move_to_pos']);
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
			$cl->removePackageFromGroup($pid, $_POST['remove_from_group_id']);
		}
		die();
	}

	if(isset($_POST['create_group'])) {
		die(strval(intval(
			$cl->createPackageGroup($_POST['create_group'], empty($_POST['parent_id']) ? null : intval($_POST['parent_id']))
		)));
	}

	if(!empty($_POST['rename_group_id']) && isset($_POST['new_name'])) {
		$cl->renamePackageGroup($_POST['rename_group_id'], $_POST['new_name']);
		die();
	}

	if(isset($_POST['add_to_group_id']) && isset($_POST['add_to_group_package_id']) && is_array($_POST['add_to_group_package_id'])) {
		foreach($_POST['add_to_group_package_id'] as $pid) {
			$cl->addPackageToGroup($pid, $_POST['add_to_group_id']);
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

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG['permission_denied']);
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
