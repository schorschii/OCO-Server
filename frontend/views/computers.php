<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$group = null;
$computer = [];
if(empty($_GET['id'])) {
	$computer = $db->getAllComputer();
	echo "<h1><img src='img/computer.dyn.svg'><span id='page-title'>".LANG['all_computer']."</span></h1>";

	echo "<div class='controls'>";
	echo "<button onclick='createComputer()'><img src='img/add.svg'>&nbsp;".LANG['new_computer']."</button> ";
	echo "<button onclick='createComputerGroup()'><img src='img/folder-new.svg'>&nbsp;".LANG['new_group']."</button> ";
	echo "<span class='fillwidth'></span> ";
	echo "<span><a target='_blank' href='https://github.com/schorschii/oco-agent' title='".LANG['agent_download_description']."'>".LANG['agent_download']."</a></span> ";
	echo "</div>";
} else {
	$computer = $db->getComputerByGroup($_GET['id']);
	$group = $db->getComputerGroup($_GET['id']);
	if($group === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
	echo "<h1><img src='img/folder.dyn.svg'><span id='page-title'>".htmlspecialchars($db->getComputerGroupBreadcrumbString($group->id))."</span><span id='spnComputerGroupName' class='rawvalue'>".htmlspecialchars($group->name)."</span></h1>";

	echo "<div class='controls'><span>".LANG['group'].":&nbsp;</span>";
	echo "<button onclick='createComputerGroup(".$group->id.")'><img src='img/folder-new.svg'>&nbsp;".LANG['new_subgroup']."</button> ";
	echo "<button onclick='refreshContentDeploy([],[],[],[".$group->id."])'><img src='img/deploy.svg'>&nbsp;".LANG['deploy_for_all']."</button> ";
	echo "<button onclick='renameComputerGroup(".$group->id.", this.getAttribute(\"oldName\"))' oldName='".htmlspecialchars($group->name,ENT_QUOTES)."'><img src='img/edit.svg'>&nbsp;".LANG['rename_group']."</button> ";
	echo "<button onclick='confirmRemoveComputerGroup([".$group->id."], event, spnComputerGroupName.innerText)'><img src='img/delete.svg'>&nbsp;".LANG['delete_group']."</button> ";
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
		<th class='searchable sortable'><?php echo LANG['ram']; ?></th>
		<th class='searchable sortable'><?php echo LANG['ip_addresses']; ?></th>
		<th class='searchable sortable'><?php echo LANG['mac_addresses']; ?></th>
		<th class='searchable sortable'><?php echo LANG['model']; ?></th>
		<th class='searchable sortable'><?php echo LANG['serial_no']; ?></th>
		<th class='searchable sortable'><?php echo LANG['agent']; ?></th>
		<th class='searchable sortable'><?php echo LANG['notes']; ?></th>
		<th class='searchable sortable'><?php echo LANG['last_seen']; ?></th>
	</tr>
</thead>

<tbody>
<?php
$counter = 0;
foreach($computer as $c) {
	$counter ++;
	$ip_addresses = [];
	$mac_addresses = [];
	$cnetwork = $db->getComputerNetwork($c->id);
	foreach($cnetwork as $n) {
		if(!(empty($n->addr) || $n->addr == '-' || $n->addr == '?')) $ip_addresses[] = $n->addr;
		if(!(empty($n->mac) || $n->mac == '-' || $n->mac == '?')) $mac_addresses[] = $n->mac;
	}
	$online = $c->isOnline();
	echo "<tr>";
	echo "<td><input type='checkbox' name='computer_id[]' value='".$c->id."' onchange='refreshCheckedCounter(tblComputerData)'></td>";
	echo "<td>";
	echo  "<img src='".$c->getIcon()."' class='".($online ? 'online' : 'offline')."' title='".($online ? LANG['online'] : LANG['offline'])."'>&nbsp;";
	echo  "<a ".explorerLink('views/computer-details.php?id='.$c->id).">".htmlspecialchars($c->hostname)."</a>";
	echo "</td>";
	echo "<td>".htmlspecialchars($c->os)."</td>";
	echo "<td>".htmlspecialchars($c->os_version)."</td>";
	echo "<td sort_key='".htmlspecialchars($c->ram)."'>".htmlspecialchars(niceSize($c->ram, true, 0))."</td>";
	echo "<td>".htmlspecialchars(implode($ip_addresses,', '))."</td>";
	echo "<td>".htmlspecialchars(implode($mac_addresses,', '))."</td>";
	echo "<td>".htmlspecialchars($c->manufacturer.' '.$c->model)."</td>";
	echo "<td>".htmlspecialchars($c->serial)."</td>";
	echo "<td>".htmlspecialchars($c->agent_version)."</td>";
	echo "<td>".htmlspecialchars(shorter($c->notes))."</td>";
	echo "<td>".htmlspecialchars($c->last_ping)."</td>";
	echo "</tr>";
}
?>
</tbody>

<tfoot>
	<tr>
		<td colspan='999'>
			<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>,
			<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
			<a href='#' onclick='event.preventDefault();downloadTableCsv("tblComputerData")'><?php echo LANG['csv']; ?></a>
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
			<?php echoComputerGroupOptions($db); ?>
		</select>
	</button>
	<?php if($group !== null) { ?>
		<button onclick='removeSelectedComputerFromGroup("computer_id[]", <?php echo $group->id; ?>)'><img src='img/folder-remove-from.svg'>&nbsp;<?php echo LANG['remove_from_group']; ?></button>
	<?php } ?>
	<button onclick='removeSelectedComputer("computer_id[]", null, event)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
