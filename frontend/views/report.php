<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(empty($_GET['id'])) {
	$reports = $db->getAllReport();
} else {
	$reportGroup = $db->getReportGroup($_GET['id']);
	if(empty($reportGroup)) die(LANG['not_found']);
	$reports = $db->getAllReportByGroup($reportGroup->id);
}
?>

<?php if(empty($_GET['id'])) { ?>
	<h1><?php echo LANG['reports']; ?></h1>
<?php } else { ?>
	<h1><?php echo htmlspecialchars($reportGroup->name); ?></h1>
<?php } ?>

<p><?php echo LANG['report_creation_notes']; ?></p>

<table id='tblReportsData' class='list searchable sortable savesort'>
<thead>
	<tr><th class='searchable sortable'><?php echo LANG['name']; ?></th></tr>
</thead>
<tbody>
	<?php foreach($reports as $r) {
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentReportDetail(".$r->id.")'>".htmlspecialchars($r->name)."</a></td>";
		echo "</tr>";
	} ?>
</tbody>
<tfoot>
	<tr>
		<td colspan='999'>
			<span class='counter'><?php echo count($reports); ?></span> <?php echo LANG['elements']; ?>,
			<a href='#' onclick='event.preventDefault();downloadTableCsv("tblReportsData")'><?php echo LANG['csv']; ?></a>
		</td>
	</tr>
</tfoot>
</table>
