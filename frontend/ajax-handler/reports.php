<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

try {

	if(!empty($_POST['update_report_id'])
	&& isset($_POST['name'])
	&& isset($_POST['notes'])
	&& isset($_POST['query'])) {
		$cl->updateReport($_POST['update_report_id'], $_POST['name'], $_POST['notes'], $_POST['query']);
		die();
	}

	if(!empty($_POST['create_report'])
	&& isset($_POST['notes'])
	&& isset($_POST['query'])
	&& isset($_POST['group_id'])) {
		die(strval(intval(
			$cl->createReport($_POST['create_report'], $_POST['notes'], $_POST['query'], $_POST['group_id'])
		)));
	}

	if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
		foreach($_POST['remove_id'] as $id) {
			$cl->removeReport($id);
		}
		die();
	}

	if(!empty($_POST['create_group'])) {
		die(strval(intval(
			$cl->createReportGroup($_POST['create_group'], empty($_POST['parent_id']) ? null : intval($_POST['parent_id']))
		)));
	}

	if(!empty($_POST['rename_group_id']) && isset($_POST['new_name'])) {
		$cl->renameReportGroup($_POST['rename_group_id'], $_POST['new_name']);
		die();
	}

	if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
		foreach($_POST['remove_group_id'] as $id) {
			$cl->removeReportGroup($id, !empty($_POST['force']));
		}
		die();
	}

	if(isset($_POST['move_to_group_id']) && isset($_POST['move_to_group_report_id']) && is_array($_POST['move_to_group_report_id'])) {
		foreach($_POST['move_to_group_report_id'] as $rid) {
			$cl->moveReportToGroup($rid, intval($_POST['move_to_group_id']));
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
