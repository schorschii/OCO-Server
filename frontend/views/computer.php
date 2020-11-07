<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['wol_id']) && is_array($_POST['wol_id'])) {
	foreach($_POST['wol_id'] as $id) {
		$c = $db->getComputer($id);
		if($c != null) {
			foreach($db->getComputerNetwork($c->id) as $n) {
				wol($n->mac);
			}
		}
	}
	die();
}
if(!empty($_POST['add_computer'])) {
	$db->addComputer($_POST['add_computer'], '', []);
	die();
}
if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
	foreach($_POST['remove_id'] as $id) {
		$db->removeComputer($id);
	}
	die();
}
if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
	foreach($_POST['remove_group_id'] as $id) {
		$db->removeComputerGroup($id);
	}
	die();
}
if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_computer_id']) && is_array($_POST['remove_from_group_computer_id'])) {
	foreach($_POST['remove_from_group_computer_id'] as $cid) {
		$db->removeComputerFromGroup($cid, $_POST['remove_from_group_id']);
	}
	die();
}
if(!empty($_POST['add_group'])) {
	$db->addComputerGroup($_POST['add_group']);
	die();
}
if(!empty($_POST['add_to_group_id']) && !empty($_POST['add_to_group_computer_id']) && is_array($_POST['add_to_group_computer_id'])) {
	foreach($_POST['add_to_group_computer_id'] as $cid) {
		if(count($db->getComputerByComputerAndGroup($cid, $_POST['add_to_group_id'])) == 0) {
			$db->addComputerToGroup($cid, $_POST['add_to_group_id']);
		}
	}
	die();
}

$group = null;
$computer = [];
if(empty($_GET['id'])) {
	$computer = $db->getAllComputer();
	echo "<h1>".LANG['all_computer']."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='newComputer()'><img src='img/add.svg'>&nbsp;".LANG['new_computer']."</button> ";
	echo "<button onclick='newComputerGroup()'><img src='img/folder-new.svg'>&nbsp;".LANG['new_group']."</button> ";
	echo "</div>";
} else {
	$computer = $db->getComputerByGroup($_GET['id']);
	$group = $db->getComputerGroup($_GET['id']);
	if($group === null) die();
	echo "<h1>".htmlspecialchars($group->name)."</h1>";

	echo "<div class='controls'><span>Gruppe:&nbsp;</span>";
	echo "<button onclick='refreshContentDeploy([],[],[],[".$group->id."])'><img src='img/deploy.svg'>&nbsp;".LANG['deploy_for_all']."</button> ";
	echo "<button onclick='confirmRemoveComputerGroup([".$group->id."])'><img src='img/delete.svg'>&nbsp;".LANG['delete_group']."</button> ";
	echo "</div>";
}
?>

<table id='tblComputerData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblComputerData, this.checked)'></th>
		<th class='searchable sortable'><?php echo LANG['hostname']; ?></th>
		<th class='searchable sortable'><?php echo LANG['os']; ?></th>
		<th class='searchable sortable'><?php echo LANG['version']; ?></th>
		<th class='searchable sortable'><?php echo LANG['cpu']; ?></th>
		<th class='searchable sortable'><?php echo LANG['ram']; ?></th>
		<th class='searchable sortable'><?php echo LANG['ip_addresses']; ?></th>
		<th class='searchable sortable'><?php echo LANG['mac_addresses']; ?></th>
		<th class='searchable sortable'><?php echo LANG['serial_no']; ?></th>
		<th class='searchable sortable'><?php echo LANG['notes']; ?></th>
		<th class='searchable sortable'><?php echo LANG['agent']; ?></th>
		<th class='searchable sortable'><?php echo LANG['last_seen']; ?></th>
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
	echo "<td><input type='checkbox' name='computer_id[]' value='".$c->id."' onchange='refreshCheckedCounter(tblComputerData)'></td>";
	echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(\"".$c->id."\")'>".htmlspecialchars($c->hostname)."</a></td>";
	echo "<td>".htmlspecialchars($c->os)."</td>";
	echo "<td>".htmlspecialchars($c->os_version)."</td>";
	echo "<td>".htmlspecialchars($c->cpu)."</td>";
	echo "<td>".bytesToGb($c->ram)."</td>";
	echo "<td>".htmlspecialchars(implode($ip_addresses,', '))."</td>";
	echo "<td>".htmlspecialchars(implode($mac_addresses,', '))."</td>";
	echo "<td>".htmlspecialchars($c->serial)."</td>";
	echo "<td>".htmlspecialchars($c->notes)."</td>";
	echo "<td>".htmlspecialchars($c->agent_version)."</td>";
	echo "<td>".htmlspecialchars($c->last_ping);
	echo "</tr>";
}
?>

<tfoot>
	<tr>
		<td colspan='999'>
			<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>,
			<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
		</td>
	</tr>
</tfoot>
</table>

<div class='controls'>
	<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
	<button onclick='deploySelectedComputer("computer_id[]")'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<button onclick='wolSelectedComputer("computer_id[]")'><img src='img/wol.svg'>&nbsp;<?php echo LANG['wol']; ?></button>
	<button onclick='addSelectedComputerToGroup("computer_id[]", sltNewGroup.value)'><img src='img/folder-insert-into.svg'>
		&nbsp;<?php echo LANG['add_to']; ?>
		<select id='sltNewGroup' onclick='event.stopPropagation()'>
			<?php
			foreach($db->getAllComputerGroup() as $g) {
				echo "<option value='".$g->id."'>".htmlspecialchars($g->name)."</option>";
			}
			?>
		</select>
	</button>
	<?php if($group !== null) { ?>
		<button onclick='removeSelectedComputerFromGroup("computer_id[]", <?php echo $group->id; ?>)'><img src='img/folder-remove-from.svg'>&nbsp;<?php echo LANG['remove_from_group']; ?></button>
	<?php } ?>
	<button onclick='removeSelectedComputer("computer_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
