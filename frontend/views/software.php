<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

if(!$currentSystemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SOFTWARE_VIEW, false))
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
?>

<?php
if(!empty($_GET['id'])) {
	$software = $db->selectSoftware($_GET['id']);
	if($software === null) die("<div class='alert warning'>".LANG('not_found')."</div>");
?>


<div class='details-header'>
	<h1><img src='img/software.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($software->name) . ' ' . htmlspecialchars($software->version); ?></span></h1>
</div>
<?php if(!empty($software->description)) { ?>
	<p class='quote'><?php echo nl2br(htmlspecialchars($software->description)); ?></p>
<?php } ?>
<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('installed_on'); ?></h2>
		<table id='tblSoftwareComputerData1' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('hostname'); ?></th>
					<th class='searchable sortable'><?php echo LANG('os'); ?></th>
					<th class='searchable sortable'><?php echo LANG('version'); ?></th>
				</tr>
			</thead>
			<?php
			$counter = 0;
			foreach($db->selectAllComputerBySoftwareId($_GET['id']) as $c) {
				$counter ++;
				echo "<tr>";
				echo "<td><a ".explorerLink('views/computer-details.php?id='.$c->id).">".htmlspecialchars($c->hostname)."</a></td>";
				echo "<td>".htmlspecialchars($c->os)."</td>";
				echo "<td>".htmlspecialchars($c->os_version)."</td>";
				echo "</tr>";
			}
			?>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button onclick='event.preventDefault();downloadTableCsv("tblSoftwareComputerData1")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>


<?php
} elseif(!empty($_GET['name'])) {
?>


<div class='details-header'>
	<h1><img src='img/software.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($_GET['name']); ?></span></h1>
</div>
<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('installed_on'); ?></h2>
		<table id='tblSoftwareComputerData2' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('hostname'); ?></th>
					<th class='searchable sortable'><?php echo LANG('version'); ?></th>
				</tr>
			</thead>
			<?php
			$counter = 0;
			foreach($db->selectAllComputerBySoftwareName($_GET['name']) as $c) {
				$counter ++;
				echo "<tr>";
				echo "<td><a ".explorerLink('views/computer-details.php?id='.$c->id).">".htmlspecialchars($c->hostname)."</a></td>";
				echo "<td><a ".explorerLink('views/software.php?id='.$c->software_id).">".htmlspecialchars($c->software_version)."</a></td>";
				echo "</tr>";
			}
			?>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button onclick='event.preventDefault();downloadTableCsv("tblSoftwareComputerData2")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>


<?php } else { ?>


<div class='details-header'>
	<h1><img src='img/software.dyn.svg'><span id='page-title'><?php echo LANG('recognised_software'); ?></span></h1>
</div>
<div class='details-abreast'>
	<div class='stickytable'>
		<?php $software = [];
		if(isset($_GET['os']) && $_GET['os'] == 'windows') {
			echo "<h2>".LANG('windows')."</h2>";
			$software = $db->selectAllSoftwareByComputerOsWindows();
		} elseif(isset($_GET['os']) && $_GET['os'] == 'macos') {
			echo "<h2>".LANG('macos')."</h2>";
			$software = $db->selectAllSoftwareByComputerOsMacOs();
		} elseif(isset($_GET['os']) && $_GET['os'] == 'other') {
			echo "<h2>".LANG('linux')."</h2>";
			$software = $db->selectAllSoftwareByComputerOsOther();
		} else {
			echo "<h2>".LANG('all_os')."</h2>";
			$software = $db->selectAllSoftware();
		}
		?>
		<table id='tblSoftwareData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('installations'); ?></th>
			</tr>
		</thead>
		<?php
		$counter = 0;
		foreach($software as $s) {
			$counter ++;
			echo "<tr>";
			echo "<td><a ".explorerLink('views/software.php?name='.urlencode($s->name)).">".htmlspecialchars($s->name)."</a></td>";
			echo "<td>".$s->installations."</td>";
			echo "</tr>";
		}
		?>
		<tfoot>
			<tr>
				<td colspan='999'>
					<div class='spread'>
						<div>
							<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG('elements'); ?>
						</div>
						<div class='controls'>
							<button onclick='event.preventDefault();downloadTableCsv("tblSoftwareData")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>


<?php } ?>
