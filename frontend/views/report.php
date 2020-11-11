<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(empty($_GET['id'])) {
	echo "<h1>".LANG['reports']."</h1>";

	foreach($db->getAllReport() as $r) {
		echo "<a href='#' onclick='event.preventDefault();refreshContentReport(".$r->id.")'>".htmlspecialchars($r->name)."</a><br>";
	}
} else {
	$report = $db->getReport($_GET['id']);
	if($report === null) die();
	echo "<h1>".htmlspecialchars($report->name)."</h1>";
	$results = $db->executeReport($report->id);
	if(count($results) == 0) die();
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
foreach($results as $r) {
	$counter ++;
	echo "<tr>";
	foreach(get_object_vars($results[0]) as $key => $value) {
		echo "<td class='searchable sortable'>".$value."</td>";
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
