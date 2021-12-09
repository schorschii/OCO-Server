<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(!empty($_POST['update_report_id']) && isset($_POST['update_name'])) {
		$report = $db->getReport($_POST['update_report_id']);
		if($report == null || empty(trim($_POST['update_name']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->updateReport($report->id, $report->report_group_id, $_POST['update_name'], $report->notes, $report->query);
		die();
	}

	if(!empty($_POST['update_report_id']) && isset($_POST['update_note'])) {
		$report = $db->getReport($_POST['update_report_id']);
		if($report == null) {
			throw new Exception(LANG['not_found']);
		}
		$db->updateReport($report->id, $report->report_group_id, $report->name, $_POST['update_note'], $report->query);
		die();
	}

	if(!empty($_POST['update_report_id']) && isset($_POST['update_query'])) {
		$report = $db->getReport($_POST['update_report_id']);
		if($report == null || empty(trim($_POST['update_query']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$db->updateReport($report->id, $report->report_group_id, $report->name, $report->notes, $_POST['update_query']);
		die();
	}

	if(!empty($_POST['add_report']) && isset($_POST['query']) && isset($_POST['group_id'])) {
		if(empty(trim($_POST['add_report'])) || empty(trim($_POST['query']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$insertId = $db->addReport($_POST['group_id'], $_POST['add_report'], '', $_POST['query']);
		die(strval(intval($insertId)));
	}

	if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
		foreach($_POST['remove_id'] as $id) {
			$db->removeReport($id);
		}
		die();
	}

	if(!empty($_POST['add_group'])) {
		if(empty(trim($_POST['add_group']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		$insertId = -1;
		if(empty($_POST['parent_id'])) $insertId = $db->addReportGroup($_POST['add_group']);
		else $insertId = $db->addReportGroup($_POST['add_group'], intval($_POST['parent_id']));
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
