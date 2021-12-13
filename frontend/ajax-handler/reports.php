<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
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
			$db->removeReport($id);
		}
		die();
	}

	if(!empty($_POST['create_group'])) {
		if(empty(trim($_POST['create_group']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$insertId = -1;
		if(empty($_POST['parent_id'])) $insertId = $db->addReportGroup($_POST['create_group']);
		else $insertId = $db->addReportGroup($_POST['create_group'], intval($_POST['parent_id']));
		die(strval(intval($insertId)));
	}

	if(!empty($_POST['rename_group']) && isset($_POST['new_name'])) {
		if(empty(trim($_POST['new_name']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->renameReportGroup($_POST['rename_group'], $_POST['new_name']);
		die();
	}

	if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
		foreach($_POST['remove_group_id'] as $id) {
			$cl->removeReportGroup($id, !empty($_POST['force']));
		}
		die();
	}

	if(isset($_POST['move_to_group_id']) && isset($_POST['move_to_group_report_id']) && is_array($_POST['move_to_group_report_id'])) {
		if($db->getReportGroup($_POST['move_to_group_id']) == null) {
			throw new Exception(LANG['not_found']);
		}
		foreach($_POST['move_to_group_report_id'] as $rid) {
			$report = $db->getReport($rid);
			if($report == null) continue;
			$db->updateReport($report->id, intval($_POST['move_to_group_id']), $report->name, $report->notes, $report->query);
		}
		die();
	}

} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
