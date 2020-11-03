<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');
?>

<?php
if(!empty($_GET['id']) && !empty($_GET['version'])) {
	$software = $db->getSoftware($_GET['id']);
	if($software === null) die('not found');
?>


	<h1><?php echo htmlspecialchars($software->name) . ' ' . htmlspecialchars($_GET['version']); ?></h1>
	<table id='tblSoftwareComputerData1' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG['hostname']; ?></th>
		</tr>
	</thead>
	<?php
	$counter = 0;
	foreach($db->getComputerBySoftwareVersion($_GET['id'], $_GET['version']) as $c) {
		$counter ++;
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(\"".$c->id."\")'>".htmlspecialchars($c->hostname)."</a></td>";
		echo "</tr>";
	}
	?>
	<tfoot>
		<tr>
			<td colspan='999'>
				<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>
			</td>
		</tr>
	</tfoot>
	</table>


<?php
} elseif(!empty($_GET['id'])) {
	$software = $db->getSoftware($_GET['id']);
	if($software === null) die('not found');
?>


	<h1><?php echo htmlspecialchars($software->name); ?></h1>
	<table id='tblSoftwareComputerData2' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG['hostname']; ?></th>
			<th class='searchable sortable'><?php echo LANG['version']; ?></th>
		</tr>
	</thead>
	<?php
	$counter = 0;
	foreach($db->getComputerBySoftware($_GET['id'], $_GET['version']) as $c) {
		$counter ++;
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(\"".$c->id."\")'>".htmlspecialchars($c->hostname)."</a></td>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentSoftware(".$software->id.", \"".htmlspecialchars($c->version)."\")'>".htmlspecialchars($c->version)."</a></td>";
		echo "</tr>";
	}
	?>
	<tfoot>
		<tr>
			<td colspan='999'>
				<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>
			</td>
		</tr>
	</tfoot>
	</table>


<?php } else { ?>


	<h1><?php echo LANG['recognised_software']; ?></h1>
	<table id='tblSoftwareData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG['name']; ?></th>
			<th class='searchable sortable'><?php echo LANG['installations']; ?></th>
		</tr>
	</thead>
	<?php
	$counter = 0;
	foreach($db->getAllSoftware() as $s) {
		$counter ++;
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentSoftware(\"".$s->id."\")'>".htmlspecialchars($s->name)."</a></td>";
		echo "<td>".$s->installations."</td>";
		echo "</tr>";
	}
	?>
	<tfoot>
		<tr>
			<td colspan='999'>
				<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>
			</td>
		</tr>
	</tfoot>
	</table>


<?php } ?>
