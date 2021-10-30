<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(!empty($_POST['update_report_id']) && !empty($_POST['update_name'])) {
	$report = $db->getReport($_POST['update_report_id']);
	if($report == null || empty(trim($_POST['update_name']))) {
		header('HTTP/1.1 400 Invalid Request');
		die(LANG['name_cannot_be_empty']);
	}
	$db->updateReport($report->id, $report->report_group_id, $_POST['update_name'], $report->notes, $report->query);
	die();
}
if(!empty($_POST['update_report_id']) && isset($_POST['update_note'])) {
	$report = $db->getReport($_POST['update_report_id']);
	if($report == null) {
		header('HTTP/1.1 400 Invalid Request');
		die(LANG['name_cannot_be_empty']);
	}
	$db->updateReport($report->id, $report->report_group_id, $report->name, $_POST['update_note'], $report->query);
	die();
}
if(!empty($_POST['update_report_id']) && !empty($_POST['update_query'])) {
	$report = $db->getReport($_POST['update_report_id']);
	if($report == null || empty(trim($_POST['update_query']))) {
		header('HTTP/1.1 400 Invalid Request');
		die(LANG['name_cannot_be_empty']);
	}
	$db->updateReport($report->id, $report->report_group_id, $report->name, $report->notes, $_POST['update_query']);
	die();
}

$report = $db->getReport($_GET['id'] ?? -1);
if($report === null) die("<div class='alert warning'>".LANG['not_found']."</div>");

$results = [];
$error = null;
try {
	$results = $db->executeReport($report->id);
} catch (Exception $e) {
	$error = 'SQL-Error: '.$e->getMessage();
}
?>

<div class='details-header'>
	<h1><img src='img/report.dyn.svg'><?php echo htmlspecialchars($report->name); ?></h1>
	<div class='controls'>
		<button onclick='renameReport(<?php echo $report->id; ?>, this.getAttribute("oldValue"))' oldValue='<?php echo htmlspecialchars($report->name,ENT_QUOTES); ?>'><img src='img/edit.svg'>&nbsp;<?php echo LANG['rename']; ?></button>
		<button onclick='editReportQuery(<?php echo $report->id; ?>, this.getAttribute("oldValue"))' oldValue='<?php echo htmlspecialchars($report->query,ENT_QUOTES); ?>'><img src='img/edit.svg'>&nbsp;<?php echo LANG['edit_query']; ?></button>
		<button onclick='editReportNote(<?php echo $report->id; ?>, this.getAttribute("oldValue"))' oldValue='<?php echo htmlspecialchars($report->notes,ENT_QUOTES); ?>'><img src='img/edit.svg'>&nbsp;<?php echo LANG['edit_description']; ?></button>
		<button onclick='currentExplorerContentUrl="views/reports.php";confirmRemoveReport([<?php echo $report->id; ?>])'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['query']; ?></h2>
		<code class='block'><?php echo htmlspecialchars($report->query); ?></code>
	</div>
	<div>
		<h2><?php echo LANG['description']; ?></h2>
		<?php if(!empty($report->notes)) { ?>
			<p class='quote'><?php echo nl2br(htmlspecialchars($report->notes)); ?></p>
		<?php } ?>
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
					echo "<td><a href='".explorerLink('views/computer-detail.php?id='.intval($value))."' onclick='event.preventDefault();refreshContentComputerDetail(\"".intval($value)."\")'>".$value."</a></td>";
				} elseif($key == 'package_id') {
					$hasPackageIds = true;
					echo "<td><a href='".explorerLink('views/package-detail.php?id='.intval($value))."' onclick='event.preventDefault();refreshContentPackageDetail(\"".intval($value)."\")'>".$value."</a></td>";
				} elseif($key == 'software_id') {
					echo "<td><a href='".explorerLink('views/software.php?id='.intval($value))."' onclick='event.preventDefault();refreshContentSoftware(\"".intval($value)."\")'>".$value."</a></td>";
				} elseif($key == 'domainuser_id') {
					echo "<td><a href='".explorerLink('views/domainuser-detail.php?id='.intval($value))."' onclick='event.preventDefault();refreshContentDomainuser(\"".intval($value)."\")'>".$value."</a></td>";
				} elseif($key == 'jobcontainer_id') {
					echo "<td><a href='".explorerLink('views/job-containers.php?id='.intval($value))."' onclick='event.preventDefault();refreshContentJobContainer(\"".intval($value)."\")'>".$value."</a></td>";
				} elseif($key == 'report_id') {
					echo "<td><a href='".explorerLink('views/report-detail.php?id='.intval($value))."' onclick='event.preventDefault();refreshContentReport(\"".intval($value)."\")'>".$value."</a></td>";
				} elseif($key == 'parent_report_id') {
					echo "<td><a href='".explorerLink('views/report-detail.php?id='.intval($value))."' onclick='event.preventDefault();refreshContentReport(\"".intval($value)."\")'>".$value."</a></td>";
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
				<button onclick='deploySelectedComputer("id[]", "computer_id")'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
				<button onclick='wolSelectedComputer("id[]", "computer_id")'><img src='img/wol.svg'>&nbsp;<?php echo LANG['wol']; ?></button>
				<button onclick='addSelectedComputerToGroup("id[]", sltNewComputerGroup.value, "computer_id")'><img src='img/folder-insert-into.svg'>
					&nbsp;<?php echo LANG['add_to']; ?>
					<select id='sltNewComputerGroup' onclick='event.stopPropagation()'>
						<?php echoComputerGroupOptions($db); ?>
					</select>
				</button>
				<button onclick='removeSelectedComputer("id[]", "computer_id", event)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
			</div>
		<?php } ?>
		<?php if($hasPackageIds) { ?>
			<div class='controls'>
				<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
				<button onclick='deploySelectedPackage("id[]", "package_id")'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
				<button onclick='addSelectedPackageToGroup("id[]", sltNewPackageGroup.value, "package_id")'><img src='img/folder-insert-into.svg'>
					&nbsp;<?php echo LANG['add_to']; ?>
					<select id='sltNewPackageGroup' onclick='event.stopPropagation()'>
						<?php echoPackageGroupOptions($db); ?>
					</select>
				</button>
				<button onclick='removeSelectedPackage("id[]", "package_id", event)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
			</div>
		<?php } ?>

		<?php } ?>
	</div>
</div>
