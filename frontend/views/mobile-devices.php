<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$group = null;
$mobileDevices = [];
$subGroups = [];
try {
	if(!empty($_GET['id'])) {
		$group = $cl->getMobileDeviceGroup($_GET['id']);
		$mobileDevices = $cl->getMobileDevices($group);
	} else {
		$mobileDevices = $cl->getMobileDevices();
	}
	$subGroups = $cl->getMobileDeviceGroups(empty($group) ? null : $group->id);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if($group === null) {
	$permissionCreateMobileDevice = $cl->checkPermission(new Models\MobileDevice(), PermissionManager::METHOD_CREATE, false);
	$permissionCreateGroup = $cl->checkPermission(new Models\MobileDeviceGroup(), PermissionManager::METHOD_CREATE, false);
	$permissionGeneral = $cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION, false);
?>
	<h1><img src='img/mobile-device.dyn.svg'><span id='page-title'><?php echo LANG('all_mobile_devices'); ?></span></h1>
	<div class='controls'>
		<button onclick='showDialogCreateMobileDeviceIos()' <?php if(!$permissionCreateMobileDevice) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_ios_device'); ?></button>
		<button onclick='showDialogCreateMobileDeviceAndroid()' <?php if(!$permissionCreateMobileDevice) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_android_device'); ?></button>
		<button onclick='createMobileDeviceGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_group'); ?></button>
		<span class='filler'></span>
		<button onclick='syncAppleDevices(this)' <?php if(!$permissionGeneral) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('sync_with_apple_business_manager'); ?></button>
		<button onclick='syncAndroidDevices(this)' <?php if(!$permissionGeneral) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('sync_with_android_enterprise'); ?></button>
	</div>
<?php } else {
	$permissionCreate = $cl->checkPermission($group, PermissionManager::METHOD_CREATE, false);
	$permissionWrite  = $cl->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $cl->checkPermission($group, PermissionManager::METHOD_DELETE, false);
?>
	<h1><img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($db->getMobileDeviceGroupBreadcrumbString($group->id)); ?></span><span id='spnMobileDeviceGroupName' class='rawvalue'><?php echo htmlspecialchars($group->name); ?></span></h1>
	<div class='controls'>
		<button onclick='createMobileDeviceGroup(<?php echo $group->id; ?>)' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_subgroup'); ?></button>
		<button onclick='renameMobileDeviceGroup(<?php echo $group->id; ?>, this.getAttribute("oldName"))' oldName='<?php echo htmlspecialchars($group->name,ENT_QUOTES); ?>' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('rename_group'); ?></button>
		<button onclick='confirmRemoveMobileDeviceGroup([<?php echo $group->id; ?>], event, spnMobileDeviceGroupName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete_group'); ?></button>
		<span class='filler'></span>
	</div>
<?php } ?>

<?php if(!empty($subGroups) || $group != null) { ?>
<div class='controls subfolders'>
	<?php if($group != null) { ?>
		<?php if($group->parent_mobile_device_group_id == null) { ?>
			<a class='box' <?php echo explorerLink('views/mobile-devices.php'); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo LANG('all_mobile_devices'); ?></a>
		<?php } else { $subGroup = $cl->getMobileDeviceGroup($group->parent_mobile_device_group_id); ?>
			<a class='box' <?php echo explorerLink('views/mobile-devices.php?id='.$group->parent_mobile_device_group_id); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo htmlspecialchars($subGroup->name); ?></a>
		<?php } ?>
	<?php } ?>
	<?php foreach($subGroups as $g) { ?>
		<a class='box' <?php echo explorerLink('views/mobile-devices.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
	<?php } ?>
</div>
<?php } ?>
<?php if($group != null) { ?>
	<div class='controls subfolders'>
		<?php foreach($db->selectAllProfileByMobileDeviceGroupId($group->id) as $p) { ?>
			<a class='box deletable' href='#' title='<?php echo LANG('remove_assignment'); ?>' onclick='removeProfileFromGroup([<?php echo $p->id; ?>],<?php echo $group->id; ?>);return false;'><img src='img/profile.dyn.svg'>&nbsp;<?php echo htmlspecialchars($p->name); ?></a>
		<?php } ?>
		<?php foreach($db->selectAllManagedAppByMobileDeviceGroupId($group->id) as $ma) { ?>
			<a class='box deletable' href='#' title='<?php echo LANG('remove_assignment'); ?>' onclick='removeManagedAppFromGroup([<?php echo $ma->id; ?>],<?php echo $group->id; ?>);return false;'><img src='img/store.dyn.svg'>&nbsp;<?php echo htmlspecialchars($ma->name); ?></a>
		<?php } ?>
	</div>
<?php } ?>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblMobileDeviceData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('device_name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('serial_no'); ?></th>
				<th class='searchable sortable'><?php echo LANG('os'); ?></th>
				<th class='searchable sortable'><?php echo LANG('model'); ?></th>
				<th class='searchable sortable'><?php echo LANG('mac_addresses'); ?></th>
				<th class='searchable sortable'><?php echo LANG('notes'); ?></th>
				<th class='searchable sortable'><?php echo LANG('last_seen'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($mobileDevices as $md) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='mobile_device_id[]' value='".$md->id."'></td>";
			echo "<td>";
			echo  "<img src='".$md->getIcon()."' class='".($md->isOnline() ? 'online' : 'offline')."' title='".($md->isOnline() ? LANG('enrolled') : LANG('not_enrolled'))."'>&nbsp;";
			echo  "<a ".explorerLink('views/mobile-device-details.php?id='.$md->id).">".htmlspecialchars($md->device_name?$md->device_name:$md->serial)."</a>";
			echo "</td>";
			echo "<td>".htmlspecialchars($md->serial)."</td>";
			echo "<td>".htmlspecialchars($md->os)."</td>";
			echo "<td>".htmlspecialchars($md->model)."</td>";
			echo "<td>".htmlspecialchars(implode(', ',$md->getMacAddresses()))."</td>";
			echo "<td>".htmlspecialchars(shorter(LANG($md->notes)))."</td>";
			echo "<td>".htmlspecialchars($md->last_update??'')."</td>";
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
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							<button onclick='showDialogAddMobileDeviceToGroup(getSelectedCheckBoxValues("mobile_device_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
							<?php if($group !== null) { ?>
								<button onclick='removeSelectedMobileDeviceFromGroup("mobile_device_id[]", <?php echo $group->id; ?>)'><img src='img/folder-remove-from.dyn.svg'>&nbsp;<?php echo LANG('remove_from_group'); ?></button>
							<?php } ?>
							<button onclick='removeSelectedMobileDevice("mobile_device_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
