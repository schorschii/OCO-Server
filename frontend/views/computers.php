<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$group = null;
$computers = [];
$subGroups = [];
$policyObjects = [];
try {
	if(!empty($_GET['id'])) {
		$group = $cl->getComputerGroup($_GET['id']);
		$computers = $cl->getComputers($group);
	} else {
		$computers = $cl->getComputers();
	}
	$subGroups = $cl->getComputerGroups($group ? $group->id : null);
	$policyObjects = $db->selectAllPolicyObjectByComputerGroup($group ? $group->id : null);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if($group === null) {
	$permissionCreateComputer = $cl->checkPermission(new Models\Computer(), PermissionManager::METHOD_CREATE, false);
	$permissionCreateGroup    = $cl->checkPermission(new Models\ComputerGroup(), PermissionManager::METHOD_CREATE, false);
?>
	<h1><img src='img/computer.dyn.svg'><span id='page-title'><?php echo LANG('all_computers'); ?></span></h1>
	<div class='controls'>
		<button onclick='showDialogCreateComputer()' <?php if(!$permissionCreateComputer) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_computer'); ?></button>
		<button onclick='createComputerGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_group'); ?></button>
		<span class='filler'></span>
		<span><a target='_blank' href='https://github.com/schorschii/oco-agent' title='<?php echo LANG('agent_download_description'); ?>'><?php echo LANG('agent_download'); ?></a></span>
	</div>
<?php } else {
	$permissionCreate = $cl->checkPermission($group, PermissionManager::METHOD_CREATE, false);
	$permissionDeploy = !empty($computers) && $cl->checkPermission($computers[0], PermissionManager::METHOD_DEPLOY, false);
	$permissionWrite  = $cl->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $cl->checkPermission($group, PermissionManager::METHOD_DELETE, false);
?>
	<h1><img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($group->getBreadcrumbString()); ?></span><span id='spnComputerGroupName' class='rawvalue'><?php echo htmlspecialchars($group->name); ?></span></h1>
	<div class='controls'>
		<button onclick='createComputerGroup(
			<?php echo $group->id; ?>
			)'
			<?php if(!$permissionCreate) echo 'disabled'; ?>>
			<img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_subgroup'); ?>
		</button>
		<button onclick='refreshContentDeploy(
			[],[],[],
			{"id":<?php echo $group->id; ?>,"name":spnComputerGroupName.innerText}
			)'
			<?php if(!$permissionDeploy) echo 'disabled'; ?>>
			<img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy_for_all'); ?>
		</button>
		<button onclick='renameComputerGroup(
			<?php echo $group->id; ?>,
			this.getAttribute("oldName")
			)'
			oldName='<?php echo htmlspecialchars($group->name,ENT_QUOTES); ?>'
			<?php if(!$permissionWrite) echo 'disabled'; ?>>
			<img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('rename_group'); ?>
		</button>
		<button onclick='confirmRemoveComputerGroup(
			[<?php echo $group->id; ?>],
			event,
			spnComputerGroupName.innerText
			)'
			<?php if(!$permissionDelete) echo 'disabled'; ?>>
			<img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete_group'); ?>
		</button>
		<span class='filler'></span>
	</div>
<?php } ?>

<?php if(!empty($subGroups) || $group != null) { ?>
<div class='controls subfolders'>
	<?php if($group != null) { ?>
		<?php if($group->parent_computer_group_id == null) { ?>
			<a class='box' <?php echo Html::explorerLink('views/computers.php'); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo LANG('all_computers'); ?></a>
		<?php } else { $subGroup = $cl->getComputerGroup($group->parent_computer_group_id); ?>
			<a class='box' <?php echo Html::explorerLink('views/computers.php?id='.$group->parent_computer_group_id); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo htmlspecialchars($subGroup->name); ?></a>
		<?php } ?>
	<?php } ?>
	<?php foreach($subGroups as $g) { ?>
		<a class='box' <?php echo Html::explorerLink('views/computers.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
	<?php } ?>
</div>
<?php } ?>

<?php if(!empty($policyObjects)) { ?>
<div class='controls subfolders'>
	<?php foreach($policyObjects as $po) { ?>
		<a class='box' <?php echo Html::explorerLink('views/policy-object.php?id='.$po->id); ?>><img src='img/policy.dyn.svg'>&nbsp;<?php echo htmlspecialchars($po->name); ?></a>
	<?php } ?>
</div>
<?php } ?>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblComputerData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('hostname'); ?></th>
				<th class='searchable sortable'><?php echo LANG('os'); ?></th>
				<th class='searchable sortable'><?php echo LANG('version'); ?></th>
				<th class='searchable sortable'><?php echo LANG('ram'); ?></th>
				<th class='searchable sortable'><?php echo LANG('ip_addresses'); ?></th>
				<th class='searchable sortable'><?php echo LANG('mac_addresses'); ?></th>
				<th class='searchable sortable'><?php echo LANG('model'); ?></th>
				<th class='searchable sortable'><?php echo LANG('serial_no'); ?></th>
				<th class='searchable sortable'><?php echo LANG('agent'); ?></th>
				<th class='searchable sortable'><?php echo LANG('notes'); ?></th>
				<th class='searchable sortable'><?php echo LANG('last_seen'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($computers as $c) {
			$ip_addresses = [];
			$mac_addresses = [];
			$cnetwork = $db->selectAllComputerNetworkByComputerId($c->id);
			foreach($cnetwork as $n) {
				if(!(empty($n->address) || $n->address == '-' || $n->address == '?')) $ip_addresses[] = $n->address;
				if(!(empty($n->mac) || $n->mac == '-' || $n->mac == '?')) $mac_addresses[] = $n->mac;
			}
			$online = $c->isOnline($db);
			echo "<tr>";
			echo "<td><input type='checkbox' name='computer_id[]' value='".$c->id."'></td>";
			echo "<td>";
			echo  "<img src='".$c->getIcon()."' class='".($online ? 'online' : 'offline')."' title='".($online ? LANG('online') : LANG('offline'))."'>&nbsp;";
			echo  "<a ".Html::explorerLink('views/computer-details.php?id='.$c->id).">".htmlspecialchars($c->hostname)."</a>";
			echo "</td>";
			echo "<td>".htmlspecialchars($c->os)."</td>";
			echo "<td>".htmlspecialchars($c->os_version)."</td>";
			echo "<td sort_key='".htmlspecialchars($c->ram)."'>".htmlspecialchars(niceSize($c->ram, true, 0))."</td>";
			echo "<td>".htmlspecialchars(implode(', ',$ip_addresses))."</td>";
			echo "<td>".htmlspecialchars(implode(', ',$mac_addresses))."</td>";
			echo "<td>".htmlspecialchars($c->manufacturer.' '.$c->model)."</td>";
			echo "<td>".htmlspecialchars($c->serial)."</td>";
			echo "<td>".htmlspecialchars($c->agent_version)."</td>";
			echo "<td>".htmlspecialchars(shorter(LANG($c->notes)))."</td>";
			echo "<td>".htmlspecialchars($c->last_ping??'')."</td>";
			echo "</tr>";
		}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='999'>
					<div class='spread'>
						<div>
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
							<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
						</div>
						<div class='controls'>
							<button class='downloadCsv'>
								<img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?>
							</button>
							<button onclick='deploySelectedComputer("computer_id[]")'>
								<img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?>
							</button>
							<button onclick='wolSelectedComputer("computer_id[]")'>
								<img src='img/wol.dyn.svg'>&nbsp;<?php echo LANG('wol'); ?>
							</button>
							<button onclick='showDialogAddComputerToGroup(getSelectedCheckBoxValues("computer_id[]", null, true))' title='<?php echo LANG('add_to_group',ENT_QUOTES); ?>'>
								<img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add'); ?>
							</button>
							<?php if($group !== null) { ?>
								<button onclick='removeSelectedComputerFromGroup("computer_id[]", <?php echo $group->id; ?>)' title='<?php echo LANG('remove_from_group',ENT_QUOTES); ?>'><img src='img/folder-remove-from.dyn.svg'>&nbsp;<?php echo LANG('remove'); ?></button>
							<?php } ?>
							<button onclick='removeSelectedComputer(getSelectedCheckBoxValues("computer_id[]",null,true), event)'>
								<img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?>
							</button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
