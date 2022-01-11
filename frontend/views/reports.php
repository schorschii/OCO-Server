<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$group = null;
$reports = [];
try {
	if(!empty($_GET['id'])) {
		$group = $cl->getReportGroup($_GET['id']);
		$reports = $cl->getReports($group);
	} else {
		$reports = $cl->getReports();
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG['not_found']."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG['permission_denied']."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if($group === null) { ?>
	<h1><img src='img/report.dyn.svg'><span id='page-title'><?php echo LANG['reports']; ?></span></h1>
	<div class='controls'>
		<button onclick='showDialogCreateReport()'><img src='img/add.svg'>&nbsp;<?php echo LANG['new_report']; ?></button>
		<button onclick='createReportGroup()'><img src='img/folder-new.svg'>&nbsp;<?php echo LANG['new_group']; ?></button>
		<span class='fillwidth'></span>
		<span><a target='_blank' href='img/dbschema.png' title='<?php echo LANG['database_schema_description']; ?>'><?php echo LANG['database_schema']; ?></a></span>
	</div>
<?php } else { ?>
	<h1><img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($db->getReportGroupBreadcrumbString($group->id)); ?></span><span id='spnReportGroupName' class='rawvalue'><?php echo htmlspecialchars($group->name); ?></span></h1>
	<div class='controls'><span><?php echo LANG['group']; ?>:&nbsp;</span>
		<button onclick='showDialogCreateReport("<?php echo $group->id; ?>")'><img src='img/add.svg'>&nbsp;<?php echo LANG['new_report']; ?></button>
		<button onclick='createReportGroup(<?php echo $group->id; ?>)'><img src='img/folder-new.svg'>&nbsp;<?php echo LANG['new_subgroup']; ?></button>
		<button onclick='renameReportGroup(<?php echo $group->id; ?>, spnReportGroupName.innerText)'><img src='img/edit.svg'>&nbsp;<?php echo LANG['rename_group']; ?></button>
		<button onclick='confirmRemoveReportGroup([<?php echo $group->id; ?>], event, spnReportGroupName.innerText)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete_group']; ?></button>
	</div>
<?php } ?>

<div class='details-abreast'>
	<div>
		<table id='tblReportData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblReportData, this.checked)'></th>
				<th class='searchable sortable'><?php echo LANG['name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['description']; ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($reports as $r) {
				echo "<tr>";
				echo "<td><input type='checkbox' name='report_id[]' value='".$r->id."' onchange='refreshCheckedCounter(tblReportData)'></td>";
				echo "<td><a ".explorerLink('views/report-details.php?id='.$r->id).">".htmlspecialchars($r->name)."</a></td>";
				echo "<td>".htmlspecialchars(shorter($r->notes))."</td>";
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
