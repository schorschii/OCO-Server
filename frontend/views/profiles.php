<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$profilesIos = $cl->getProfilesByType(Models\Profile::TYPE_IOS);
	$profilesAndroid = $cl->getProfilesByType(Models\Profile::TYPE_ANDROID);
	$permissionCreateProfile = $cl->checkPermission(new Models\Profile(), PermissionManager::METHOD_CREATE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1><img src='img/profile.dyn.svg'><span id='page-title'><?php echo LANG('profiles_and_policies'); ?></span></h1>
<div class='controls'>
	<button onclick='showDialogEditProfile("<?php echo Models\Profile::TYPE_IOS; ?>")' <?php if(!$permissionCreateProfile) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_ios_profile'); ?></button>
	<button onclick='showDialogEditProfile("<?php echo Models\Profile::TYPE_ANDROID; ?>")' <?php if(!$permissionCreateProfile) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_android_policy'); ?></button>
	<span class='filler'></span>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('ios'); ?></h2>
		<table id='tblIosProfileData' class='list searchable sortable savesort actioncolumn'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name').'/'.LANG('notes'); ?></th>
				<th class='searchable sortable'><?php echo LANG('created'); ?></th>
				<th class='searchable sortable'><?php echo LANG('groups'); ?></th>
				<th class=''><?php echo LANG('action'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($profilesIos as $p) {
			$groupLinks = [];
			foreach($db->selectAllMobileDeviceGroupByProfileId($p->id) as $group)
				$groupLinks[] = "<a ".explorerLink('views/mobile-devices.php?id='.$group->id).">".htmlspecialchars($group->name)."</a>";
			echo "<tr>";
			echo "<td><input type='checkbox' name='profile_id[]' value='".$p->id."'></td>";
			echo "<td><div id='divProfile".$p->id."'>".htmlspecialchars($p->name)."</div><div class='hint'>".htmlspecialchars(shorter(LANG($p->notes)))."</div></td>";
			echo "<td>".htmlspecialchars($p->created)."</td>";
			echo "<td>".implode("<br>", $groupLinks)."</td>";
			echo "<td>"
				."<button class='small' title='".LANG('show_content')."' onclick='showDialogAjax(divProfile".$p->id.".innerText, \"views/dialog-profile-details.php?id=".$p->id."\", DIALOG_BUTTONS_CLOSE)'><img src='img/eye.dyn.svg'></button>"
				."<button class='small' title='".LANG('edit')."' onclick='showDialogEditProfile(\"".Models\Profile::TYPE_IOS."\", ".$p->id.")'><img src='img/edit.dyn.svg'></button>"
				."</td>";
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
							<button onclick='showDialogAssignProfileToGroup(getSelectedCheckBoxValues("profile_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('assign'); ?></button>
							<button onclick='removeSelectedProfile("profile_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>

	<div class='stickytable'>
		<h2><?php echo LANG('android'); ?></h2>
		<table id='tblAndroidProfileData' class='list searchable sortable savesort actioncolumn'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name').'/'.LANG('notes'); ?></th>
				<th class='searchable sortable'><?php echo LANG('created'); ?></th>
				<th class='searchable sortable'><?php echo LANG('groups'); ?></th>
				<th class=''><?php echo LANG('action'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($profilesAndroid as $p) {
			$groupLinks = [];
			foreach($db->selectAllMobileDeviceGroupByProfileId($p->id) as $group)
				$groupLinks[] = "<a ".explorerLink('views/mobile-devices.php?id='.$group->id).">".htmlspecialchars($group->name)."</a>";
			echo "<tr>";
			echo "<td><input type='checkbox' name='profile_id[]' value='".$p->id."'></td>";
			echo "<td><div id='divProfile".$p->id."'>".htmlspecialchars($p->name)."</div><div class='hint'>".htmlspecialchars(shorter(LANG($p->notes)))."</div></td>";
			echo "<td>".htmlspecialchars($p->created)."</td>";
			echo "<td>".implode("<br>", $groupLinks)."</td>";
			echo "<td>"
				."<button class='small' title='".LANG('show_content')."' onclick='showDialogAjax(divProfile".$p->id.".innerText, \"views/dialog-profile-details.php?id=".$p->id."\", DIALOG_BUTTONS_CLOSE)'><img src='img/eye.dyn.svg'></button>"
				."<button class='small' title='".LANG('edit')."' onclick='showDialogEditProfile(\"".Models\Profile::TYPE_ANDROID."\", ".$p->id.")'><img src='img/edit.dyn.svg'></button>"
				."</td>";
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
							<button onclick='showDialogAssignProfileToGroup(getSelectedCheckBoxValues("profile_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('assign'); ?></button>
							<button onclick='removeSelectedProfile("profile_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
