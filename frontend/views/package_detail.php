<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');


if(!empty($_POST['edit_id'])) {
	$db->updatePackage(
		$_POST['edit_id'],
		$_POST['name'],
		$_POST['version'],
		$_POST['author'],
		$_POST['description'],
		$_POST['install_procedure'],
		$_POST['uninstall_procedure']
	);
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
<table class='list form'>
	<tr>
		<th><?php echo LANG['id']; ?></th>
		<td><?php echo htmlspecialchars($package->id); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td><input type='text' id='txtName' value='<?php echo htmlspecialchars($package->name); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['version']; ?></th>
		<td><input type='text' id='txtVersion' readonly='true' value='<?php echo htmlspecialchars($package->version); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['author']; ?></th>
		<td><input type='text' id='txtAuthor' readonly='true' value='<?php echo htmlspecialchars($package->author); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea id='txtDescription'><?php echo htmlspecialchars($package->notes); ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['install_procedure']; ?></th>
		<td><input type='text' id='txtInstallProcedure' readonly='true' value='<?php echo htmlspecialchars($package->install_procedure); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['uninstall_procedure']; ?></th>
		<td><input type='text' id='txtUninstallProcedure' readonly='true' value='<?php echo htmlspecialchars($package->uninstall_procedure); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['zip_archive']; ?></th>
		<td>
			<?php if(file_exists(PACKAGE_PATH.'/'.intval($package->id).'.zip')) { ?>
				<a href='payloadprovider.php?id=<?php echo intval($package->id) ?>' target='_blank'><?php echo LANG['download']; ?></a>
				(<?php $size = filesize(PACKAGE_PATH.'/'.intval($package->id).'.zip'); echo niceSize($size, true).', '.niceSize($size, false); ?>)
			<?php } else { ?>
				<?php echo LANG['not_found']; ?>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['created']; ?></th>
		<td><?php echo htmlspecialchars($package->created); ?></td>
	</tr>
	<tr>
		<th></th>
		<td><button id='btnEditPackage' onclick='updatePackage(<?php echo $package->id; ?>, txtName.value, txtVersion.value, txtAuthor.value, txtDescription.value, txtInstallProcedure.value, txtUninstallProcedure.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['save']; ?></button></td>
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
	<button onclick='deploySelectedComputer("package_id[]", "computer_id");'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<button onclick='addSelectedComputerToGroup("package_id[]", sltNewGroup.value, "computer_id")'><img src='img/folder-insert-into.svg'>
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
