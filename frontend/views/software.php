<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');
?>

<?php
if(!empty($_GET['id']) && !empty($_GET['version'])) {
	$software = $db->getSoftware($_GET['id']);
	if($software === null) die(LANG['not_found']);
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
	if($software === null) die(LANG['not_found']);
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
	foreach($db->getComputerBySoftware($_GET['id']) as $c) {
		$counter ++;
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(\"".$c->id."\")'>".htmlspecialchars($c->hostname)."</a></td>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentSoftware(".$software->id.", \"".htmlspecialchars($c->software_version)."\")'>".htmlspecialchars($c->software_version)."</a></td>";
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
	<?php $software = [];
	if(isset($_GET['os']) && $_GET['os'] == 'windows') {
		echo "<h2>".LANG['windows']."</h2>";
		$software = $db->getAllSoftwareWindows();
	} elseif(isset($_GET['os']) && $_GET['os'] == 'macos') {
		echo "<h2>".LANG['macos']."</h2>";
		$software = $db->getAllSoftwareMacOS();
	} elseif(isset($_GET['os']) && $_GET['os'] == 'other') {
		echo "<h2>".LANG['linux']."</h2>";
		$software = $db->getAllSoftwareOther();
	} else {
		$software = $db->getAllSoftware();
	}
	?>
	<table id='tblSoftwareData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG['name']; ?></th>
			<th class='searchable sortable'><?php echo LANG['installations']; ?></th>
		</tr>
	</thead>
	<?php
	$counter = 0;
	foreach($software as $s) {
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
