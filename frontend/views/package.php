<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
	foreach($_POST['remove_id'] as $id) {
		$db->removePackage($id);
	}
	die();
}
if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
	foreach($_POST['remove_group_id'] as $id) {
		$db->removePackageGroup($id);
	}
	die();
}
if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_package_id']) && is_array($_POST['remove_from_group_package_id'])) {
	foreach($_POST['remove_from_group_package_id'] as $pid) {
		$db->removePackageFromGroup($pid, $_POST['remove_from_group_id']);
	}
	die();
}
if(!empty($_POST['add_group'])) {
	$db->addPackageGroup($_POST['add_group']);
	die();
}
if(!empty($_POST['add_to_group_id']) && !empty($_POST['add_to_group_package_id']) && is_array($_POST['add_to_group_package_id'])) {
	foreach($_POST['add_to_group_package_id'] as $pid) {
		$db->addPackageToGroup($pid, $_POST['add_to_group_id']);
	}
	die();
}

$group = null;
$packages = [];
if(empty($_GET['id'])) {
	$packages = $db->getAllPackage();
	echo "<h1>".LANG['complete_package_library']."</h1>";

	echo "<p>";
	echo "<button onclick='refreshContentPackageDetail()'><img src='img/add.svg'>&nbsp;".LANG['new_package']."</button> ";
	echo "<button onclick='newPackageGroup()'><img src='img/folder-new.svg'>&nbsp;".LANG['new_group']."</button> ";
	echo "</p>";
} else {
	$packages = $db->getPackageByGroup($_GET['id']);
	$group = $db->getPackageGroup($_GET['id']);
	if($group === null) die();
	echo "<h1>".htmlspecialchars($group->name)."</h1>";

	echo "<p>Gruppe:&nbsp;";
	echo "<button onclick='refreshContentDeploy([],[".$group->id."])'><img src='img/deploy.svg'>&nbsp;".LANG['deploy_all']."</button> ";
	echo "<button onclick='confirmRemovePackageGroup([".$group->id."])'><img src='img/delete.svg'>&nbsp;".LANG['delete_group']."</button> ";
	echo "</p>";
}
?>

<iframe id='frmDownload' style='display:none'></iframe>

<table id='tblPackageData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th></th>
		<th class='searchable sortable'><?php echo LANG['name']; ?></th>
		<th class='searchable sortable'><?php echo LANG['version']; ?></th>
		<th class='searchable sortable'><?php echo LANG['author']; ?></th>
		<th class='searchable sortable'><?php echo LANG['install_procedure']; ?></th>
		<th class='searchable sortable'><?php echo LANG['uninstall_procedure']; ?></th>
		<th class='searchable sortable'><?php echo LANG['order']; ?></th>
		<th class='searchable sortable'><?php echo LANG['created']; ?></th>
		<th class='searchable sortable'><?php echo LANG['action']; ?></th>
	</tr>
</thead>

<?php
$counter = 0;
foreach($packages as $p) {
	$counter ++;
	echo "<tr>";
	echo "<td><input type='checkbox' name='package_id[]' value='".$p->id."'></td>";
	echo "<td><a href='#' onclick='refreshContentPackageDetail(".$p->id.")'>".htmlspecialchars($p->name)."</a></td>";
	echo "<td>".htmlspecialchars($p->version)."</td>";
	echo "<td>".htmlspecialchars($p->author)."</td>";
	echo "<td>".htmlspecialchars($p->install_procedure)."</td>";
	echo "<td>".htmlspecialchars($p->uninstall_procedure)."</td>";
	echo "<td>".htmlspecialchars($p->sequence ?? '-')."</td>";
	echo "<td>".htmlspecialchars($p->created)."</td>";
	echo "<td>";
	echo  "<button title='".LANG['download']."' onclick='frmDownload.src=\"payloadprovider.php?id=".$p->id."\"'><img src='img/download.svg'></button>";
	echo "</td>";
	echo "</tr>";
}
?>

<tfoot>
	<tr>
		<td colspan='999'><span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?></td>
	</tr>
</tfoot>
</table>

<p><?php echo LANG['selected_elements']; ?>:&nbsp;
	<button onclick='deploySelectedPackage("package_id[]")'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<button onclick='addSelectedPackageToGroup("package_id[]", sltNewGroup.value)'><img src='img/folder-insert-into.svg'>
		&nbsp;<?php echo LANG['add_to']; ?>
		<select id='sltNewGroup' onclick='event.stopPropagation()'>
			<?php
			foreach($db->getAllPackageGroup() as $g) {
				echo "<option value='".$g->id."'>".htmlspecialchars($g->name)."</option>";
			}
			?>
		</select>
	</button>
	<?php if($group !== null) { ?>
		<button onclick='removeSelectedPackageFromGroup("package_id[]", <?php echo $group->id; ?>)'><img src='img/folder-remove-from.svg'>&nbsp;<?php echo LANG['remove_from_group']; ?></button>
	<?php } ?>
	<button onclick='removeSelectedPackage("package_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</p>
