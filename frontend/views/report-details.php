<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

// ----- prepare view -----
try {
	$report = $cl->getReport($_GET['id'] ?? -1);
	$permissionWrite  = $currentSystemUser->checkPermission($report, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $currentSystemUser->checkPermission($report, PermissionManager::METHOD_DELETE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG['not_found']."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG['permission_denied']."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$results = [];
$error = null;
try {
	$results = $db->executeReport($report->id);
} catch(Exception $e) {
	$error = 'SQL-Error: '.$e->getMessage();
}
?>

<div class='details-header'>
	<h1><img src='img/report.dyn.svg'><span id='page-title'><span id='spnReportName'><?php echo htmlspecialchars($report->name); ?></span></span></h1>
	<div class='controls'>
		<button onclick='showDialogEditReport("<?php echo $report->id; ?>", spnReportName.innerText, spnReportNotes.innerText, spnReportQuery.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG['edit']; ?></button>
		<button onclick='currentExplorerContentUrl="views/reports.php";confirmRemoveReport([<?php echo $report->id; ?>], spnReportName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['query']; ?></h2>
		<code class='block'><span id='spnReportQuery'><?php echo htmlspecialchars($report->query); ?></span></code>
	</div>
	<div>
		<h2><?php echo LANG['description']; ?></h2>
		<span id='spnReportNotes'>
		<?php if(!empty($report->notes)) { ?>
			<p class='quote'><?php echo nl2br(htmlspecialchars($report->notes)); ?></p>
		<?php } ?>
		</span>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['results']; ?></h2>

		<?php if($error !== null) { ?>
			<div class='alert error'><?php echo htmlspecialchars($error); ?></div>
		<?php } elseif(count($results) == 0) { ?>
			<div class='alert info'><?php echo LANG['no_results']; ?></div>
		<?php } else { ?>

		<table id='tblReportDetailData' class='list searchable sortable'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblReportDetailData, this.checked)'></th>
				<?php foreach($results[0] as $key => $value) { ?>
				<th class='searchable sortable'><?php echo htmlspecialchars($key); ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
		<?php
		$hasComputerIds = false;
		$hasPackageIds = false;
		$counter = 0;
		foreach($results as $result) {
			$counter ++;
			echo "<tr>";

			// checkbox
			$computerId = -1; if(!empty($result['computer_id'])) $computerId = intval($result['computer_id']);
			$packageId = -1; if(!empty($result['package_id'])) $packageId = intval($result['package_id']);
			echo "<td><input type='checkbox' name='id[]' computer_id='".$computerId."' package_id='".$packageId."' onchange='refreshCheckedCounter(tblReportDetailData)'></td>";

			// attributes
			foreach($result as $key => $value) {
				if($key == 'computer_id') {
					$hasComputerIds = true;
					echo "<td><a ".explorerLink('views/computer-details.php?id='.intval($value)).">".$value."</a></td>";
				} elseif($key == 'package_id') {
					$hasPackageIds = true;
					echo "<td><a ".explorerLink('views/package-details.php?id='.intval($value)).">".$value."</a></td>";
				} elseif($key == 'software_id') {
					echo "<td><a ".explorerLink('views/software.php?id='.intval($value)).">".$value."</a></td>";
				} elseif($key == 'domain_user_id') {
					echo "<td><a ".explorerLink('views/domain-users.php?id='.intval($value)).">".$value."</a></td>";
				} elseif($key == 'job_container_id') {
					echo "<td><a ".explorerLink('views/job-containers.php?id='.intval($value)).">".$value."</a></td>";
				} elseif($key == 'report_id') {
					echo "<td><a ".explorerLink('views/report-details.php?id='.intval($value)).">".$value."</a></td>";
				} elseif($key == 'parent_report_id') {
					echo "<td><a ".explorerLink('views/report-details.php?id='.intval($value)).">".$value."</a></td>";
				} else {
					echo "<td>".$value."</td>";
				}
			}
			echo "</tr>";
		}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='999'>
					<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
					<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
					<a href='#' onclick='event.preventDefault();downloadTableCsv("tblReportDetailData")'><?php echo LANG['csv']; ?></a>
				</td>
			</tr>
		</tfoot>
		</table>
		<?php if($hasComputerIds) { ?>
			<div class='controls'>
				<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
				<button onclick='deploySelectedComputer("id[]", "computer_id")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
				<button onclick='wolSelectedComputer("id[]", "computer_id")'><img src='img/wol.dyn.svg'>&nbsp;<?php echo LANG['wol']; ?></button>
				<button onclick='addSelectedComputerToGroup("id[]", sltNewComputerGroup.value, "computer_id")'><img src='img/folder-insert-into.dyn.svg'>
					&nbsp;<?php echo LANG['add_to']; ?>
					<select id='sltNewComputerGroup' onclick='event.stopPropagation()'>
						<?php echoComputerGroupOptions($db); ?>
					</select>
				</button>
				<button onclick='removeSelectedComputer("id[]", "computer_id", event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
			</div>
		<?php } ?>
		<?php if($hasPackageIds) { ?>
			<div class='controls'>
				<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
				<button onclick='deploySelectedPackage("id[]", "package_id")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
				<button onclick='addSelectedPackageToGroup("id[]", sltNewPackageGroup.value, "package_id")'><img src='img/folder-insert-into.dyn.svg'>
					&nbsp;<?php echo LANG['add_to']; ?>
					<select id='sltNewPackageGroup' onclick='event.stopPropagation()'>
						<?php echoPackageGroupOptions($db); ?>
					</select>
				</button>
				<button onclick='removeSelectedPackage("id[]", "package_id", event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
			</div>
		<?php } ?>

		<?php } ?>
	</div>
</div>
