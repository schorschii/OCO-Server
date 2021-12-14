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
		if(empty(trim($_POST['new_name']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->renameJobContainer($_POST['edit_container_id'], $_POST['new_name']);
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_start'])) {
		if(DateTime::createFromFormat('Y-m-d H:i:s', $_POST['new_start']) === false) {
			throw new Exception(LANG['date_parse_error']);
		}
		$db->editJobContainerStart($_POST['edit_container_id'], $_POST['new_start']);
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_end'])) {
		if(!empty($_POST['new_end']) && DateTime::createFromFormat('Y-m-d H:i:s', $_POST['new_end']) === false) {
			throw new Exception(LANG['date_parse_error']);
		}
		$container = $db->getJobContainer($_POST['edit_container_id']);
		if($container == null) {
			throw new Exception(LANG['not_found']);
		}
		if(empty($_POST['new_end'])) {
			$db->editJobContainerEnd($_POST['edit_container_id'], null);
			die();
		} else {
			if(strtotime($container->start_time) > strtotime($_POST['new_end'])) {
				throw new Exception(LANG['end_time_before_start_time']);
			}
			$db->editJobContainerEnd($_POST['edit_container_id'], $_POST['new_end']);
			die();
		}
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_sequence_mode'])) {
		if(is_numeric($_POST['new_sequence_mode']) && in_array($_POST['new_sequence_mode'], [JobContainer::SEQUENCE_MODE_IGNORE_FAILED, JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED])) {
			$db->editJobContainerSequenceMode($_POST['edit_container_id'], $_POST['new_sequence_mode']);
		} else {
			throw new Exception(LANG['invalid_input']);
		}
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_priority'])) {
		if(is_numeric($_POST['new_priority']) && intval($_POST['new_priority']) > -100 && intval($_POST['new_priority']) < 100) {
			$db->editJobContainerPriority($_POST['edit_container_id'], $_POST['new_priority']);
		} else {
			throw new Exception(LANG['invalid_input']);
		}
		die();
	}

	if(!empty($_POST['edit_container_id']) && isset($_POST['new_notes'])) {
		$db->editJobContainerNotes($_POST['edit_container_id'], $_POST['new_notes']);
		die();
	}

	if(!empty($_POST['remove_container_id']) && is_array($_POST['remove_container_id'])) {
		foreach($_POST['remove_container_id'] as $id) {
			$cl->removeJobContainer($id);
		}
		die();
	}

} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
