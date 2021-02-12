<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(empty($_GET['id'])) die(LANG['not_found']);
$report = $db->getReport($_GET['id']);
if($report === null) die(LANG['not_found']);

try {
	$results = $db->executeReport($report->id);
} catch (Exception $e) {
	header('HTTP/1.1 500 Query Failed');
	die('SQL-Error: '.$e->getMessage());
}
?>

<h1><?php echo htmlspecialchars($report->name); ?></h1>

<p><code class='block'><?php echo htmlspecialchars($report->query); ?></code></p>

<?php if(!empty($report->notes)) { ?>
	<p class='quote'><?php echo htmlspecialchars($report->notes); ?></p>
<?php } ?>

<?php if(count($results) == 0) die(LANG['no_results']); ?>

<table id='tblReportData' class='list searchable sortable'>
<thead>
	<tr>
		<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblReportData, this.checked)'></th>
		<?php foreach($results[0] as $key => $value) { ?>
		<th class='searchable sortable'><?php echo htmlspecialchars($key); ?></th>
		<?php } ?>
	</tr>
</thead>

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
	echo "<td><input type='checkbox' name='id[]' computer_id='".$computerId."' package_id='".$packageId."' onchange='refreshCheckedCounter(tblReportData)'></td>";

	// attributes
	foreach($result as $key => $value) {
		if($key == 'computer_id') {
			$hasComputerIds = true;
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'package_id') {
			$hasPackageIds = true;
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentPackageDetail(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'software_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentSoftware(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'domainuser_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentDomainuserDetail(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'jobcontainer_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentJobContainer(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'report_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentReport(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'parent_report_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentReport(\"".intval($value)."\")'>".$value."</a></td>";
		} else {
			echo "<td>".$value."</td>";
		}
	}
	echo "</tr>";
}
?>

<tfoot>
	<tr>
		<td colspan='999'>
			<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
			<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
			<a href='#' onclick='event.preventDefault();downloadTableCsv("tblReportData")'><?php echo LANG['csv']; ?></a>
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
				<?php
				foreach($db->getAllComputerGroup() as $g) {
					echo "<option value='".$g->id."'>".htmlspecialchars($g->name)."</option>";
				}
				?>
			</select>
		</button>
		<button onclick='removeSelectedComputer("id[]", "computer_id")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
	</div>
<?php } ?>

<?php if($hasPackageIds) { ?>
	<div class='controls'>
		<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
		<button onclick='deploySelectedPackage("id[]", "package_id")'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
		<button onclick='addSelectedPackageToGroup("id[]", sltNewPackageGroup.value, "package_id")'><img src='img/folder-insert-into.svg'>
			&nbsp;<?php echo LANG['add_to']; ?>
			<select id='sltNewPackageGroup' onclick='event.stopPropagation()'>
			<?php
			foreach($db->getAllPackageGroup() as $g) {
				echo "<option value='".$g->id."'>".htmlspecialchars($g->name)."</option>";
			}
			?>
			</select>
		</button>
		<button onclick='removeSelectedPackage("id[]", "package_id")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
	</div>
<?php } ?>
