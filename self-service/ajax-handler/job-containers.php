<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

try {

	// ----- create install jobs if requested -----
	if(isset($_POST['create_install_job_container'])) {
		// create container + jobs
		die($cl->deploySelfService(
			$_POST['create_install_job_container'],
			$_POST['computer_id'] ?? [], $_POST['package_id'] ?? [],
			date('Y-m-d H:i:s'), null,
			$_POST['use_wol'] ?? 1, $_POST['shutdown_waked_after_completion'] ?? 0, $_POST['restart_timeout'] ?? 5,
			1 /*uninstall other versions*/, 0 /*uninstall same version*/,
			0 /*sequence_mode*/
		));
	}

	// ----- remove jobs in container if requested -----
	if(!empty($_POST['remove_job_id']) && is_array($_POST['remove_job_id'])) {
		foreach($_POST['remove_job_id'] as $id) {
			$cl->removeMyStaticJob($id);
		}
		die();
	}

	// ----- remove job container if requested -----
	if(!empty($_POST['remove_container_id']) && is_array($_POST['remove_container_id'])) {
		foreach($_POST['remove_container_id'] as $id) {
			$cl->removeMyJobContainer($id);
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
