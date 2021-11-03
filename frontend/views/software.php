<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<?php
if(!empty($_GET['id']) && !empty($_GET['version'])) {
	$software = $db->getSoftware($_GET['id']);
	if($software === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
?>


<div class='details-header'>
	<h1><img src='img/software.dyn.svg'><?php echo htmlspecialchars($software->name) . ' ' . htmlspecialchars($_GET['version']); ?></h1>
</div>
<?php if(!empty($software->description)) { ?>
	<p class='quote'><?php echo nl2br(htmlspecialchars($software->description)); ?></p>
<?php } ?>
<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['installed_on']; ?></h2>
		<table id='tblSoftwareComputerData1' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG['hostname']; ?></th>
					<th class='searchable sortable'><?php echo LANG['os']; ?></th>
					<th class='searchable sortable'><?php echo LANG['version']; ?></th>
				</tr>
			</thead>
			<?php
			$counter = 0;
			foreach($db->getComputerBySoftwareVersion($_GET['id'], $_GET['version']) as $c) {
				$counter ++;
				echo "<tr>";
				echo "<td><a href='".explorerLink('views/computer-detail.php?id='.$c->id)."' onclick='event.preventDefault();refreshContentComputerDetail(\"".$c->id."\")'>".htmlspecialchars($c->hostname)."</a></td>";
				echo "<td>".htmlspecialchars($c->os)."</td>";
				echo "<td>".htmlspecialchars($c->os_version)."</td>";
				echo "</tr>";
			}
			?>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>,
						<a href='#' onclick='event.preventDefault();downloadTableCsv("tblSoftwareComputerData1")'><?php echo LANG['csv']; ?></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>


<?php
} elseif(!empty($_GET['id'])) {
	$software = $db->getSoftware($_GET['id']);
	if($software === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
?>


<div class='details-header'>
	<h1><img src='img/software.dyn.svg'><?php echo htmlspecialchars($software->name); ?></h1>
</div>
<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['installed_on']; ?></h2>
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
				echo "<td><a href='".explorerLink('views/computer-detail.php?id='.$c->id)."' onclick='event.preventDefault();refreshContentComputerDetail(\"".$c->id."\")'>".htmlspecialchars($c->hostname)."</a></td>";
				echo "<td><a href='".explorerLink('views/software.php?id='.$software->id.'&version='.$c->software_version)."' onclick='event.preventDefault();refreshContentSoftware(".$software->id.", \"".htmlspecialchars($c->software_version)."\")'>".htmlspecialchars($c->software_version)."</a></td>";
				echo "</tr>";
			}
			?>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>,
						<a href='#' onclick='event.preventDefault();downloadTableCsv("tblSoftwareComputerData2")'><?php echo LANG['csv']; ?></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>


<?php } else { ?>


<div class='details-header'>
	<h1><img src='img/software.dyn.svg'><?php echo LANG['recognised_software']; ?></h1>
</div>
<div class='details-abreast'>
	<div>
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
			echo "<h2>".LANG['all_os']."</h2>";
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
			echo "<td><a href='".explorerLink('views/software.php?id='.$s->id)."' onclick='event.preventDefault();refreshContentSoftware(\"".$s->id."\")'>".htmlspecialchars($s->name)."</a></td>";
			echo "<td>".$s->installations."</td>";
			echo "</tr>";
		}
		?>
		<tfoot>
			<tr>
				<td colspan='999'>
					<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>,
					<a href='#' onclick='event.preventDefault();downloadTableCsv("tblSoftwareData")'><?php echo LANG['csv']; ?></a>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>


<?php } ?>
