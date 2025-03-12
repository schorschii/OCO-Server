<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$group = null;
$reports = [];
$subGroups = [];
try {
	if(!empty($_GET['id'])) {
		$group = $cl->getReportGroup($_GET['id']);
		$reports = $cl->getReports($group);
	} else {
		$reports = $cl->getReports();
	}
	$subGroups = $cl->getReportGroups(empty($group) ? null : $group->id);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if($group === null) {
	$permissionCreateReport = $cl->checkPermission(new Models\Report(), PermissionManager::METHOD_CREATE, false);
	$permissionCreateGroup  = $cl->checkPermission(new Models\ReportGroup(), PermissionManager::METHOD_CREATE, false);
?>
	<h1><img src='img/report.dyn.svg'><span id='page-title'><?php echo LANG('reports'); ?></span></h1>
	<div class='controls'>
		<button onclick='showDialogCreateReport()' <?php if(!$permissionCreateReport) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_report'); ?></button>
		<button onclick='createReportGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_group'); ?></button>
		<span class='filler'></span>
		<span><a <?php echo explorerLink('views/docs.php?page=Reports.md'); ?>><?php echo LANG('infos_and_database_schema'); ?></a></span>
	</div>
<?php } else {
	$permissionCreateReport = $cl->checkPermission(new Models\Report(), PermissionManager::METHOD_CREATE, false) && $cl->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionCreateGroup  = $cl->checkPermission($group, PermissionManager::METHOD_CREATE, false);
	$permissionWrite  = $cl->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $cl->checkPermission($group, PermissionManager::METHOD_DELETE, false);
?>
	<h1><img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($group->getBreadcrumbString()); ?></span><span id='spnReportGroupName' class='rawvalue'><?php echo htmlspecialchars($group->name); ?></span></h1>
	<div class='controls'>
		<button onclick='showDialogCreateReport("<?php echo $group->id; ?>")' <?php if(!$permissionCreateReport) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_report'); ?></button>
		<button onclick='createReportGroup(<?php echo $group->id; ?>)' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_subgroup'); ?></button>
		<button onclick='renameReportGroup(<?php echo $group->id; ?>, spnReportGroupName.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('rename_group'); ?></button>
		<button onclick='confirmRemoveReportGroup([<?php echo $group->id; ?>], event, spnReportGroupName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete_group'); ?></button>
		<span class='filler'></span>
	</div>
<?php } ?>

<?php if(!empty($subGroups) || $group != null) { ?>
<div class='controls subfolders'>
	<?php if($group != null) { ?>
		<?php if($group->parent_report_group_id == null) { ?>
			<a class='box' <?php echo explorerLink('views/reports.php'); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo LANG('reports'); ?></a>
		<?php } else { $subGroup = $cl->getReportGroup($group->parent_report_group_id); ?>
			<a class='box' <?php echo explorerLink('views/reports.php?id='.$group->parent_report_group_id); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo htmlspecialchars($subGroup->name); ?></a>
		<?php } ?>
	<?php } ?>
	<?php foreach($subGroups as $g) { ?>
		<a class='box' <?php echo explorerLink('views/reports.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
	<?php } ?>
</div>
<?php } ?>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblReportData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('description'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($reports as $r) {
				echo "<tr>";
				echo "<td><input type='checkbox' name='report_id[]' value='".$r->id."'></td>";
				echo "<td><a ".explorerLink('views/report-details.php?id='.$r->id).">".htmlspecialchars($r->name)."</a></td>";
				echo "<td>".htmlspecialchars(shorter($r->notes))."</td>";
				echo "</tr>";
			} ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='999'>
					<div class='spread'>
						<div>
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
							<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
						</div>
						<div class='controls'>
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							<button onclick='showDialogMoveReportToGroup(getSelectedCheckBoxValues("report_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('move_to'); ?></button>
							<button onclick='removeSelectedReport("report_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
