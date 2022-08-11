<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(!empty($_POST['remove_job_id']) && is_array($_POST['remove_job_id'])) {
		foreach($_POST['remove_job_id'] as $id) {
			$cl->removeJob($id);
		}
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_name'])) {
		$cl->renameJobContainer($_POST['edit_container_id'], $_POST['new_name']);
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_enabled'])) {
		$cl->updateJobContainerEnabled($_POST['edit_container_id'], $_POST['new_enabled']);
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_start'])) {
		$cl->updateJobContainerStart($_POST['edit_container_id'], $_POST['new_start']);
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_end'])) {
		$cl->updateJobContainerEnd($_POST['edit_container_id'], $_POST['new_end']);
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_sequence_mode'])) {
		$cl->updateJobContainerSequenceMode($_POST['edit_container_id'], $_POST['new_sequence_mode']);
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_priority'])) {
		$cl->updateJobContainerPriority($_POST['edit_container_id'], $_POST['new_priority']);
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_notes'])) {
		$cl->updateJobContainerNotes($_POST['edit_container_id'], $_POST['new_notes']);
		die();
	}

	if(!empty($_POST['remove_container_id']) && is_array($_POST['remove_container_id'])) {
		foreach($_POST['remove_container_id'] as $id) {
			$cl->removeJobContainer($id);
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
