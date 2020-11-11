<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['add_to_group_id']) && !empty($_POST['add_to_computer_group_package_assignment_id']) && is_array($_POST['add_to_computer_group_package_assignment_id'])) {
	foreach($_POST['add_to_computer_group_package_assignment_id'] as $pid) {
		$assignedPackage = $db->getComputerAssignedPackage($pid);
		if($assignedPackage != null) {
			if(count($db->getComputerByComputerAndGroup($assignedPackage->computer_id, $_POST['add_to_group_id'])) == 0) {
				$db->addComputerToGroup($assignedPackage->computer_id, $_POST['add_to_group_id']);
			}
		}
	}
	die();
}

$package = null;
if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
}
if($package === null) die();
?>

<h1><?php echo htmlspecialchars($package->name); ?></h1>
<div class='controls'>
	<button onclick='refreshContentDeploy([<?php echo $package->id; ?>]);'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<button onclick='currentExplorerContentUrl="views/package.php";confirmRemovePackage([<?php echo $package->id; ?>])'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>

<h2><?php echo LANG['general']; ?></h2>
<table class='list'>
	<tr>
		<th><?php echo LANG['version']; ?></th>
		<td><?php echo htmlspecialchars($package->version); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['author']; ?></th>
		<td><?php echo htmlspecialchars($package->author); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><?php echo htmlspecialchars($package->notes); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['zip_archive']; ?></th>
		<td><a href='payloadprovider.php?id=<?php echo $package->id ?>' target='_blank'><?php echo htmlspecialchars($package->filename); ?></a></td>
	</tr>
	<tr>
		<th><?php echo LANG['install_procedure']; ?></th>
		<td><?php echo htmlspecialchars($package->install_procedure); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['uninstall_procedure']; ?></th>
		<td><?php echo htmlspecialchars($package->uninstall_procedure); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['created']; ?></th>
		<td><?php echo htmlspecialchars($package->created); ?></td>
	</tr>
</table>

<h2><?php echo LANG['installed_on']; ?></h2>
<table id='tblPackageAssignedComputersData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPackageAssignedComputersData, this.checked)'></th>
			<th class='searchable sortable'><?php echo LANG['computer']; ?></th>
			<th class='searchable sortable'><?php echo LANG['procedure']; ?></th>
			<th class='searchable sortable'><?php echo LANG['installation_date']; ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$counter = 0;
	foreach($db->getPackageComputer($package->id) as $p) {
		$counter ++;
		echo '<tr>';
		echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" computer_id="'.$p->computer_id.'" onchange="refreshCheckedCounter(tblPackageAssignedComputersData)"></td>';
		echo '<td><a href="#" onclick="event.preventDefault();refreshContentComputerDetail('.$p->computer_id.')">'.htmlspecialchars($p->computer_hostname).'</a></td>';
		echo '<td>'.htmlspecialchars($p->installed_procedure).'</td>';
		echo '<td>'.htmlspecialchars($p->installed).'</td>';
		echo '</tr>';
	}
	?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan='999'>
				<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
				<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
			</td>
		</tr>
	</tfoot>
</table>
<div class='controls'>
	<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
	<button onclick='deployFromPackageDetails("package_id[]");'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<button onclick='addSelectedPackageComputerToGroup("package_id[]", sltNewGroup.value)'><img src='img/folder-insert-into.svg'>
		&nbsp;<?php echo LANG['add_to']; ?>
		<select id='sltNewGroup' onclick='event.stopPropagation()'>
			<?php
			foreach($db->getAllComputerGroup() as $g) {
				echo "<option value='".$g->id."'>".htmlspecialchars($g->name)."</option>";
			}
			?>
		</select>
	</button>
	<button onclick='confirmRemovePackageComputerAssignment("package_id[]")'><img src='img/remove.svg'>&nbsp;<?php echo LANG['remove_assignment']; ?></button>
	<button onclick='confirmUninstallPackage("package_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['uninstall_package']; ?></button>
</div>
