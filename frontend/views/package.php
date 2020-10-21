<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
	foreach($_POST['remove_id'] as $id) {
		$db->removePackage($id);
	}
}
if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
	foreach($_POST['remove_group_id'] as $id) {
		$db->removePackageGroup($id);
	}
}
if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_package_id']) && is_array($_POST['remove_from_group_package_id'])) {
	foreach($_POST['remove_from_group_package_id'] as $pid) {
		$db->removePackageFromGroup($pid, $_POST['remove_from_group_id']);
	}
}
if(!empty($_POST['add_group'])) {
	$db->addPackageGroup($_POST['add_group']);
}
if(!empty($_POST['add_to_group_id']) && !empty($_POST['add_to_group_package_id']) && is_array($_POST['add_to_group_package_id'])) {
	foreach($_POST['add_to_group_package_id'] as $pid) {
		$db->addPackageToGroup($pid, $_POST['add_to_group_id']);
	}
}

$group = null;
$packages = [];
if(empty($_GET['id'])) {
	$packages = $db->getAllPackage();
	echo "<h1>"."Komplette Paket-Bibliothek"."</h1>";

	echo "<p>";
	echo "<button onclick='refreshContentPackageDetail()'><img src='img/add.svg'>&nbsp;Neues Paket</button>";
	echo "<button onclick='newPackageGroup()'><img src='img/add.svg'>&nbsp;Neue Gruppe</button>";
	echo "</p>";
} else {
	$packages = $db->getPackageByGroup($_GET['id']);
	$group = $db->getPackageGroup($_GET['id']);
	if($group === null) die();
	echo "<h1>".htmlspecialchars($group->name)."</h1>";

	echo "<p>Gruppe:&nbsp;";
	echo "<button onclick='refreshContentDeploy([],[".$group->id."])'><img src='img/deploy.svg'>&nbsp;Alle bereitstellen</button>";
	echo "<button onclick='confirmRemovePackageGroup([".$group->id."])'><img src='img/delete.svg'>&nbsp;Gruppe löschen</button>";
	echo "</p>";
}
?>

<iframe id='frmDownload' style='display:none'></iframe>

<table id='tblPackageData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th></th>
		<th class='searchable sortable'>Name</th>
		<th class='searchable sortable'>Version</th>
		<th class='searchable sortable'>Autor</th>
		<th class='searchable sortable'>Installations-Prozedur</th>
		<th class='searchable sortable'>Deinstallations-Prozedur</th>
		<th class='searchable sortable'>Reihenfolge</th>
		<th class='searchable sortable'>Erstellt</th>
		<th class='searchable sortable'>Aktion</th>
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
	echo  "<button title='Download' onclick='frmDownload.src=\"payloadprovider.php?id=".$p->id."\"'><img src='img/download.svg'></button>";
	echo "</td>";
	echo "</tr>";
}
?>

<tfoot>
	<tr>
		<td colspan='999'><span class='counter'><?php echo $counter; ?></span> Element(e)</td>
	</tr>
</tfoot>
</table>

<p>Ausgewählte Elemente:&nbsp;
	<button onclick='deploySelectedPackage("package_id[]")'><img src='img/deploy.svg'>&nbsp;Bereitstellen</button>
	<button onclick='addSelectedPackageToGroup("package_id[]", sltNewGroup.value)'><img src='img/plus.svg'>
		&nbsp;Hinzufügen zu
		<select id='sltNewGroup' onclick='event.stopPropagation()'>
			<?php
			foreach($db->getAllPackageGroup() as $g) {
				echo "<option value='".$g->id."'>".htmlspecialchars($g->name)."</option>";
			}
			?>
		</select>
	</button>
	<?php if($group !== null) { ?>
		<button onclick='removeSelectedPackageFromGroup("package_id[]", <?php echo $group->id; ?>)'><img src='img/remove.svg'>&nbsp;Aus Gruppe entfernen</button>
	<?php } ?>
	<button onclick='removeSelectedPackage("package_id[]")'><img src='img/delete.svg'>&nbsp;Löschen</button>
</p>
