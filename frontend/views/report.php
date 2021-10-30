<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(!empty($_POST['add_report']) && !empty($_POST['query']) && isset($_POST['group_id'])) {
	if(empty(trim($_POST['add_report'])) || empty(trim($_POST['query']))) {
		header('HTTP/1.1 400 Invalid Request');
		die(LANG['name_cannot_be_empty']);
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
	$insertId = -1;
	if(empty($_POST['parent_id'])) $insertId = $db->addReportGroup($_POST['add_group']);
	else $insertId = $db->addReportGroup($_POST['add_group'], intval($_POST['parent_id']));
	die(strval(intval($insertId)));
}
if(!empty($_POST['rename_group']) && !empty($_POST['new_name'])) {
	$db->renameReportGroup($_POST['rename_group'], $_POST['new_name']);
	die();
}
if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
	foreach($_POST['remove_group_id'] as $id) {
		$db->removeReportGroup($id);
	}
	die();
}
if(!empty($_POST['move_to_group_id']) && !empty($_POST['move_to_group_report_id']) && is_array($_POST['move_to_group_report_id'])) {
	foreach($_POST['move_to_group_report_id'] as $rid) {
		$report = $db->getReport($rid);
		if($report == null) continue;
		$db->updateReport($report->id, intval($_POST['move_to_group_id']), $report->name, $report->notes, $report->query);
	}
	die();
}

if(empty($_GET['id'])) {
	$reports = $db->getAllReport();
} else {
	$reportGroup = $db->getReportGroup($_GET['id']);
	if(empty($reportGroup)) die("<div class='alert warning'>".LANG['not_found']."</div>");
	$reports = $db->getAllReportByGroup($reportGroup->id);
}
?>

<?php if(empty($_GET['id'])) { ?>
	<h1><img src='img/report.dyn.svg'><?php echo LANG['reports']; ?></h1>
	<div class='controls'>
		<button onclick='newReport()'><img src='img/add.svg'>&nbsp;<?php echo LANG['new_report']; ?></button>
		<button onclick='newReportGroup()'><img src='img/folder-new.svg'>&nbsp;<?php echo LANG['new_group']; ?></button>
		<span class='fillwidth'></span>
		<span><a target='_blank' href='img/dbschema.png' title='<?php echo LANG['database_schema_description']; ?>'><?php echo LANG['database_schema']; ?></a></span>
	</div>
<?php } else { ?>
	<h1><img src='img/folder.dyn.svg'><?php echo htmlspecialchars($db->getReportGroupBreadcrumbString($reportGroup->id)); ?></h1>
	<div class='controls'><span><?php echo LANG['group']; ?>:&nbsp;</span>
		<button onclick='newReport(<?php echo $reportGroup->id; ?>)'><img src='img/add.svg'>&nbsp;<?php echo LANG['new_report']; ?></button>
		<button onclick='newReportGroup(<?php echo $reportGroup->id; ?>)'><img src='img/folder-new.svg'>&nbsp;<?php echo LANG['new_subgroup']; ?></button>
		<button onclick='renameReportGroup(<?php echo $reportGroup->id; ?>, this.getAttribute("oldName"))' oldName='<?php echo htmlspecialchars($reportGroup->name,ENT_QUOTES); ?>'><img src='img/edit.svg'>&nbsp;<?php echo LANG['rename_group']; ?></button>
		<button onclick='confirmRemoveReportGroup([<?php echo $reportGroup->id; ?>])'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete_group']; ?></button>
	</div>
<?php } ?>

<div class='details-abreast'>
	<div>
		<table id='tblReportData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblReportData, this.checked)'></th>
				<th class='searchable sortable'><?php echo LANG['name']; ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($reports as $r) {
				echo "<tr>";
				echo "<td><input type='checkbox' name='report_id[]' value='".$r->id."' onchange='refreshCheckedCounter(tblReportData)'></td>";
				echo "<td><a href='".explorerLink('views/report-detail.php?id='.$r->id)."' onclick='event.preventDefault();refreshContentReportDetail(".$r->id.")'>".htmlspecialchars($r->name)."</a></td>";
				echo "</tr>";
			} ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='999'>
					<span class='counter'><?php echo count($reports); ?></span> <?php echo LANG['elements']; ?>,
					<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
					<a href='#' onclick='event.preventDefault();downloadTableCsv("tblReportData")'><?php echo LANG['csv']; ?></a>
				</td>
			</tr>
		</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='moveSelectedReportToGroup("report_id[]", sltNewGroup.value)'><img src='img/folder-insert-into.svg'>
				&nbsp;<?php echo LANG['move_to']; ?>
				<select id='sltNewGroup' onclick='event.stopPropagation()'>
					<?php echoReportGroupOptions($db); ?>
				</select>
			</button>
			<button onclick='removeSelectedReport("report_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
	</div>
</div>
