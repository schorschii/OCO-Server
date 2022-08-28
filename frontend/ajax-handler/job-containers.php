<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

try {

	if(!empty($_POST['remove_job_id']) && is_array($_POST['remove_job_id'])) {
		foreach($_POST['remove_job_id'] as $id) {
			$cl->removeJob($id);
		}
		die();
	}

	if(!empty($_POST['edit_job_container_id'])
	&& isset($_POST['name'])
	&& isset($_POST['enabled'])
	&& isset($_POST['start'])
	&& isset($_POST['end'])
	&& isset($_POST['sequence_mode'])
	&& isset($_POST['priority'])
	&& isset($_POST['agent_ip_ranges'])
	&& isset($_POST['notes'])) {
		$cl->updateJobContainer($_POST['edit_job_container_id'],
			$_POST['name'],
			$_POST['enabled'],
			$_POST['start'],
			$_POST['end'],
			$_POST['notes'],
			$_POST['sequence_mode'],
			$_POST['priority'],
			$_POST['agent_ip_ranges']
		);
		die();
	}

	if(!empty($_POST['remove_container_id']) && is_array($_POST['remove_container_id'])) {
		foreach($_POST['remove_container_id'] as $id) {
			$cl->removeJobContainer($id);
		}
		die();
	}

	if(isset($_POST['move_to_container_id']) && is_array($_POST['move_to_container_id']) && isset($_POST['move_to_container_job_id']) && is_array($_POST['move_to_container_job_id'])) {
		foreach($_POST['move_to_container_job_id'] as $cid) {
			foreach($_POST['move_to_container_id'] as $gid) {
				$cl->moveJobToContainer($cid, $gid);
			}
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
