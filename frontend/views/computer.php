<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

function gbSize($bytes) {
	if(empty($bytes)) return '';
	return round($bytes/1024/1024/2014).'&nbsp;GiB';
}

if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
	foreach($_POST['remove_id'] as $id) {
		$db->removeComputer($id);
	}
}
if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
	foreach($_POST['remove_group_id'] as $id) {
		$db->removeComputerGroup($id);
	}
}
if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_computer_id']) && is_array($_POST['remove_from_group_computer_id'])) {
	foreach($_POST['remove_from_group_computer_id'] as $cid) {
		$db->removeComputerFromGroup($cid, $_POST['remove_from_group_id']);
	}
}
if(!empty($_POST['add_group'])) {
	$db->addComputerGroup($_POST['add_group']);
}
if(!empty($_POST['add_to_group_id']) && !empty($_POST['add_to_group_computer_id']) && is_array($_POST['add_to_group_computer_id'])) {
	foreach($_POST['add_to_group_computer_id'] as $cid) {
		$db->addComputerToGroup($cid, $_POST['add_to_group_id']);
	}
}

$group = null;
$computer = [];
if(empty($_GET['id'])) {
	$computer = $db->getAllComputer();
	echo "<h1>"."Alle Computer"."</h1>";

	echo "<p>";
	echo "<button onclick='newComputerGroup()'><img src='img/add.svg'>&nbsp;Neue Gruppe</button>";
	echo "</p>";
} else {
	$computer = $db->getComputerByGroup($_GET['id']);
	$group = $db->getComputerGroup($_GET['id']);
	if($group === null) die();
	echo "<h1>".htmlspecialchars($group->name)."</h1>";

	echo "<p>Gruppe:&nbsp;";
	echo "<button onclick='refreshContentDeploy([],[],[],[".$group->id."])'><img src='img/deploy.svg'>&nbsp;Für alle bereitstellen</button>";
	echo "<button onclick='confirmRemoveComputerGroup([".$group->id."])'><img src='img/delete.svg'>&nbsp;Gruppe löschen</button>";
	echo "</p>";
}
?>

<table id='tblComputerData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th></th>
		<th class='searchable sortable'>Hostname</th>
		<th class='searchable sortable'>Betriebssystem</th>
		<th class='searchable sortable'>Version</th>
		<th class='searchable sortable'>CPU</th>
		<th class='searchable sortable'>RAM</th>
		<th class='searchable sortable'>IP-Adressen</th>
		<th class='searchable sortable'>MAC-Adressen</th>
		<th class='searchable sortable'>Seriennummer</th>
		<th class='searchable sortable'>Agent</th>
		<th class='searchable sortable'>Zuletzt gesehen</th>
	</tr>
</thead>

<?php
$counter = 0;
foreach($computer as $c) {
	$counter ++;
	$ip_addresses = [];
	$mac_addresses = [];
	$cnetwork = $db->getComputerNetwork($c->id);
	foreach($cnetwork as $n) {
		$ip_addresses[] = $n->addr;
		$mac_addresses[] = $n->mac;
	}
	echo "<tr>";
	echo "<td><input type='checkbox' name='computer_id[]' value='".$c->id."'></td>";
	echo "<td><a href='#' onclick='refreshContentComputerDetail(\"".$c->id."\")'>".htmlspecialchars($c->hostname)."</a></td>";
	echo "<td>".htmlspecialchars($c->os)."</td>";
	echo "<td>".htmlspecialchars($c->os_version)."</td>";
	echo "<td>".htmlspecialchars($c->cpu)."</td>";
	echo "<td>".gbSize($c->ram)."</td>";
	echo "<td>".htmlspecialchars(implode($ip_addresses,', '))."</td>";
	echo "<td>".htmlspecialchars(implode($mac_addresses,', '))."</td>";
	echo "<td>".htmlspecialchars($c->serial)."</td>";
	echo "<td>".htmlspecialchars($c->agent_version)."</td>";
	echo "<td>".htmlspecialchars($c->last_ping);
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
	<button onclick='deploySelectedComputer("computer_id[]")'><img src='img/deploy.svg'>&nbsp;Bereitstellen</button>
	<button onclick='addSelectedComputerToGroup("computer_id[]", sltNewGroup.value)'><img src='img/plus.svg'>
		&nbsp;Hinzufügen zu
		<select id='sltNewGroup' onclick='event.stopPropagation()'>
			<?php
			foreach($db->getAllComputerGroup() as $g) {
				echo "<option value='".$g->id."'>".htmlspecialchars($g->name)."</option>";
			}
			?>
		</select>
	</button>
	<?php if($group !== null) { ?>
		<button onclick='removeSelectedComputerFromGroup("computer_id[]", <?php echo $group->id; ?>)'><img src='img/remove.svg'>&nbsp;Aus Gruppe entfernen</button>
	<?php } ?>
	<button onclick='removeSelectedComputer("computer_id[]")'><img src='img/delete.svg'>&nbsp;Löschen</button>
</p>
