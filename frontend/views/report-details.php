<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

// ----- prepare view -----
try {
	$report = $cl->getReport($_GET['id'] ?? -1);
	$permissionWrite  = $cl->checkPermission($report, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $cl->checkPermission($report, PermissionManager::METHOD_DELETE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$results = [];
$error = null;
try {
	$results = $cl->executeReport($report->id);
} catch(Exception $e) {
	$error = 'SQL-Error: '.$e->getMessage();
}
?>

<div class='details-header'>
	<h1><img src='img/report.dyn.svg'><span id='page-title'><span id='spnReportName'><?php echo htmlspecialchars($report->name); ?></span></span></h1>
	<div class='controls'>
		<button onclick='showDialogEditReport("<?php echo $report->id; ?>", spnReportName.innerText, spnReportNotes.innerText, spnReportQuery.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
		<button onclick='confirmRemoveReport([<?php echo $report->id; ?>], spnReportName.innerText, "views/reports.php")' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
		<span class='filler'></span>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG('query'); ?></h2>
		<code class='block'><span id='spnReportQuery'><?php echo nl2br(htmlspecialchars($report->query)); ?></span></code>
	</div>
	<div>
		<h2><?php echo LANG('description'); ?></h2>
		<span id='spnReportNotes'>
		<?php if(!empty($report->notes)) { ?>
			<p class='quote'><?php echo nl2br(htmlspecialchars($report->notes)); ?></p>
		<?php } ?>
		</span>
	</div>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('results'); ?></h2>

		<?php if($error !== null) { ?>
			<div class='alert error'><?php echo htmlspecialchars($error); ?></div>
		<?php } elseif(count($results) == 0) { ?>
			<div class='alert info'><?php echo LANG('no_results'); ?></div>
		<?php } else { ?>

		<table id='<?php echo 'tblReportDetailData'.$report->id; ?>' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<?php foreach($results[0] as $key => $value) { ?>
				<th class='searchable sortable'><?php echo htmlspecialchars($key); ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
		<?php
		$hasComputerIds = false;
		$hasPackageIds = false;
		foreach($results as $result) {
			echo "<tr>";

			// checkbox
			$computerId = -1; if(!empty($result['computer_id'])) $computerId = intval($result['computer_id']);
			$packageId = -1; if(!empty($result['package_id'])) $packageId = intval($result['package_id']);
			echo "<td><input type='checkbox' name='id[]' computer_id='".$computerId."' package_id='".$packageId."'></td>";

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
				} elseif($key == 'mobile_device_id') {
					echo "<td><a ".explorerLink('views/mobile-device-details.php?id='.intval($value)).">".$value."</a></td>";
				} elseif($key == 'app_id') {
					echo "<td><a ".explorerLink('views/apps.php?id='.intval($value)).">".$value."</a></td>";
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
					<div class='spread'>
						<div>
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
							<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
						</div>
						<div class='controls'>
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							<?php if($hasComputerIds xor $hasPackageIds) { ?>
								<?php if($hasComputerIds) { ?>
									<button onclick='deploySelectedComputer("id[]", "computer_id")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
									<button onclick='wolSelectedComputer("id[]", "computer_id")'><img src='img/wol.dyn.svg'>&nbsp;<?php echo LANG('wol'); ?></button>
									<button onclick='showDialogAddComputerToGroup(getSelectedCheckBoxValues("id[]", "computer_id", true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
									<button onclick='removeSelectedComputer("id[]", "computer_id", event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
								<?php } ?>
								<?php if($hasPackageIds) { ?>
									<button onclick='deploySelectedPackage("id[]", "package_id")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
									<button onclick='showDialogAddPackageToGroup(getSelectedCheckBoxValues("id[]", "package_id", true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
									<button onclick='removeSelectedPackage("id[]", "package_id", event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
								<?php } ?>
							<?php } ?>
						</div>
					</div>
				</td>
			</tr>

			<?php if($hasComputerIds && $hasPackageIds) { ?>
				<?php if($hasComputerIds) { ?>
					<tr>
						<td colspan='999'>
							<div class='spread'>
								<div>
									<?php echo LANG('computers'); ?>
								</div>
								<div class='controls'>
									<button onclick='deploySelectedComputer("id[]", "computer_id")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
									<button onclick='wolSelectedComputer("id[]", "computer_id")'><img src='img/wol.dyn.svg'>&nbsp;<?php echo LANG('wol'); ?></button>
									<button onclick='showDialogAddComputerToGroup(getSelectedCheckBoxValues("id[]", "computer_id", true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
									<button onclick='removeSelectedComputer("id[]", "computer_id", event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
								</div>
							</div>
						</td>
					</tr>
				<?php } ?>
				<?php if($hasPackageIds) { ?>
					<tr>
						<td colspan='999'>
							<div class='spread'>
								<div>
									<?php echo LANG('software_packages'); ?>
								</div>
								<div class='controls'>
									<button onclick='deploySelectedPackage("id[]", "package_id")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
									<button onclick='showDialogAddPackageToGroup(getSelectedCheckBoxValues("id[]", "package_id", true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
									<button onclick='removeSelectedPackage("id[]", "package_id", event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
								</div>
							</div>
						</td>
					</tr>
				<?php } ?>
			<?php } ?>
		</tfoot>
		</table>

		<?php } ?>
	</div>
</div>
