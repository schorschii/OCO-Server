<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(empty($_GET['id'])) {
	echo "<h1>".LANG['reports']."</h1>";
	echo "<p>".LANG['report_creation_notes']."</p>";

	echo "<table id='tblReportsData' class='list sortable savesort'>";
	echo "<thead>";
	echo "<tr><th>".LANG['name']."</th></tr>";
	echo "</thead>";
	echo "<tbody>";
	foreach($db->getAllReport() as $r) {
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentReport(".$r->id.")'>".htmlspecialchars($r->name)."</a></td>";
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
} else {
	$report = $db->getReport($_GET['id']);
	if($report === null) die();
	echo "<h1>".htmlspecialchars($report->name)."</h1>";
	$results = $db->executeReport($report->id);
	echo "<p><code>".htmlspecialchars($report->query)."</code></p>";
	if(count($results) == 0) die(LANG['no_results']);
?>

<table class='list searchable sortable'>
<thead>
	<tr>
		<?php foreach(get_object_vars($results[0]) as $key => $value) { ?>
		<th class='searchable sortable'><?php echo htmlspecialchars($key); ?></th>
		<?php } ?>
	</tr>
</thead>

<?php
$counter = 0;
foreach($results as $result) {
	$counter ++;
	echo "<tr>";
	foreach(get_object_vars($result) as $key => $value) {
		if($key == 'computer_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'package_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentPackageDetail(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'software_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentSoftware(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'domainuser_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentDomainuserDetail(\"".intval($value)."\")'>".$value."</a></td>";
		} elseif($key == 'jobcontainer_id') {
			echo "<td><a href='#' onclick='event.preventDefault();refreshContentJobContainer(\"".intval($value)."\")'>".$value."</a></td>";
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
			<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
		</td>
	</tr>
</tfoot>
</table>

<?php } ?>
