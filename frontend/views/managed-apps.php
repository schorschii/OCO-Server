<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$iosApps = $cl->getManagedApps(Models\ManagedApp::TYPE_IOS);
	$androidApps = $cl->getManagedApps(Models\ManagedApp::TYPE_ANDROID);
	$permissionSync = $cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_MOBILE_DEVICE_SYNC, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1><img src='img/store.dyn.svg'><span id='page-title'><?php echo LANG('managed_apps'); ?></span></h1>
<div class='controls'>
	<button onclick='window.open("https://business.apple.com/#/main/appsandbooks", "_blank").focus();' <?php if(!$permissionSync) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('manage_ios_apps'); ?></button>
	<button onclick='showDialogManagedPlayStore()' <?php if(!$permissionSync) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('manage_android_apps'); ?></button>
	<span class='filler'></span>
	<button onclick='syncAppleAssets(this)' <?php if(!$permissionSync) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('sync_apple_vpp'); ?></button>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('ios'); ?></h2>
		<table id='tblManagedAppIosData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('identifier'); ?></th>
				<th class='searchable sortable'><?php echo LANG('vpp_amount'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($iosApps as $a) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='managed_app_ios_id[]' value='".$a->id."'></td>";
			echo "<td>".htmlspecialchars($a->name)."</td>";
			echo "<td>".htmlspecialchars($a->identifier)."</td>";
			echo "<td>".htmlspecialchars($a->vpp_amount??'-')."</td>";
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
							<button onclick='showDialogAssignManagedAppToGroup(getSelectedCheckBoxValues("managed_app_ios_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('assign'); ?></button>
							<button onclick='removeSelectedManagedApp("managed_app_ios_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>

	<div class='stickytable'>
		<h2><?php echo LANG('android'); ?></h2>
		<table id='tblManagedAppAndroidData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('identifier'); ?></th>
				<!--<th><?php echo LANG('action'); ?></th>-->
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($androidApps as $a) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='managed_app_android_id[]' value='".$a->id."'></td>";
			echo "<td>".htmlspecialchars($a->name)."</td>";
			echo "<td>".htmlspecialchars($a->identifier)."</td>";
			//echo "<td><button class='small' onclick='showDialogManagedPlayStoreConfig(\"".htmlspecialchars($a->identifier)."\")'><img src='img/settings.dyn.svg'></button></td>";
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
							<button onclick='showDialogAssignManagedAppToGroup(getSelectedCheckBoxValues("managed_app_android_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('assign'); ?></button>
							<button onclick='removeSelectedManagedApp("managed_app_android_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
