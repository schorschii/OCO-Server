<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$group = null;
$computers = [];
try {
	if(!empty($_GET['id'])) {
		$group = $cl->getComputerGroup($_GET['id']);
		$computers = $cl->getComputers($group);
	} else {
		$computers = $cl->getComputers();
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG['not_found']."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG['permission_denied']."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if($group === null) {
	$permissionCreateComputer = $currentSystemUser->checkPermission(new Computer(), PermissionManager::METHOD_CREATE, false);
	$permissionCreateGroup    = $currentSystemUser->checkPermission(new ComputerGroup(), PermissionManager::METHOD_CREATE, false);
?>
	<h1><img src='img/computer.dyn.svg'><span id='page-title'><?php echo LANG['all_computer']; ?></span></h1>
	<div class='controls'>
		<button onclick='createComputer()' <?php if(!$permissionCreateComputer) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG['new_computer']; ?></button>
		<button onclick='createComputerGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG['new_group']; ?></button>
		<span class='fillwidth'></span>
		<span><a target='_blank' href='https://github.com/schorschii/oco-agent' title='<?php echo LANG['agent_download_description']; ?>'><?php echo LANG['agent_download']; ?></a></span>
	</div>
<?php } else {
	$permissionCreate = $currentSystemUser->checkPermission($group, PermissionManager::METHOD_CREATE, false);
	$permissionDeploy = !empty($computers) && $currentSystemUser->checkPermission($computers[0], PermissionManager::METHOD_DEPLOY, false);
	$permissionWrite  = $currentSystemUser->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $currentSystemUser->checkPermission($group, PermissionManager::METHOD_DELETE, false);
?>
	<h1><img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($db->getComputerGroupBreadcrumbString($group->id)); ?></span><span id='spnComputerGroupName' class='rawvalue'><?php echo htmlspecialchars($group->name); ?></span></h1>
	<div class='controls'><span><?php echo LANG['group']; ?>:&nbsp;</span>
		<button onclick='createComputerGroup(<?php echo $group->id; ?>)' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG['new_subgroup']; ?></button>
		<button onclick='refreshContentDeploy([],[],[],[<?php echo $group->id; ?>])' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG['deploy_for_all']; ?></button>
		<button onclick='renameComputerGroup(<?php echo $group->id; ?>, this.getAttribute("oldName"))' oldName='<?php echo htmlspecialchars($group->name,ENT_QUOTES); ?>' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG['rename_group']; ?></button>
		<button onclick='confirmRemoveComputerGroup([<?php echo $group->id; ?>], event, spnComputerGroupName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete_group']; ?></button>
	</div>
<?php } ?>

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
foreach($computers as $c) {
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
	<button onclick='deploySelectedComputer("computer_id[]")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<button onclick='wolSelectedComputer("computer_id[]")'><img src='img/wol.dyn.svg'>&nbsp;<?php echo LANG['wol']; ?></button>
	<button onclick='showDialogAddComputerToGroup(getSelectedCheckBoxValues("computer_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG['add_to']; ?></button>
	<?php if($group !== null) { ?>
		<button onclick='removeSelectedComputerFromGroup("computer_id[]", <?php echo $group->id; ?>)'><img src='img/folder-remove-from.dyn.svg'>&nbsp;<?php echo LANG['remove_from_group']; ?></button>
	<?php } ?>
	<button onclick='removeSelectedComputer("computer_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
