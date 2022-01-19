<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG['settings']; ?></span></h1>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['general']; ?></h2>
		<p>
			<?php echo LANG['change_settings_in_config_file']; ?>
		</p>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG['client_api_enabled']; ?>:</th>
				<td><?php if(CLIENT_API_ENABLED) echo LANG['yes']; else echo LANG['no']; ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['agent_registration_enabled']; ?>:</th>
				<td><?php if(AGENT_SELF_REGISTRATION_ENABLED) echo LANG['yes']; else echo LANG['no']; ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['agent_update_interval']; ?>:</th>
				<td><?php echo htmlspecialchars(AGENT_UPDATE_INTERVAL); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['purge_succeeded_jobs_after']; ?>:</th>
				<td><?php echo htmlspecialchars(PURGE_SUCCEEDED_JOBS_AFTER); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['purge_failed_jobs_after']; ?>:</th>
				<td><?php echo htmlspecialchars(PURGE_FAILED_JOBS_AFTER); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['assume_computer_offline_after']; ?>:</th>
				<td><?php echo htmlspecialchars(COMPUTER_OFFLINE_SECONDS); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['purge_domain_user_logons_after']; ?>:</th>
				<td><?php echo htmlspecialchars(PURGE_DOMAIN_USER_LOGONS_AFTER); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['purge_logs_after']; ?>:</th>
				<td><?php echo htmlspecialchars(PURGE_LOGS_AFTER); ?></td>
			</tr>
		</table>
	</div>
	<div>
		<h2><?php echo LANG['user_settings']; ?></h2>
		<?php if(!$currentSystemUser->ldap) { ?>
			<button onclick='showDialogEditOwnSystemUserPassword()'><img src='img/password.svg'>&nbsp;<?php echo LANG['change_password']; ?></button>
		<?php } ?>
		<button onclick='askNotificationPermission()'><img src='img/notification.svg'>&nbsp;<?php echo LANG['enable_notifications']; ?></button>
	</div>
</div>

<?php if($currentSystemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT, false)) { ?>
<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['system_users']; ?></h2>
		<table id='tblSystemUserData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemUserData, this.checked)'></th>
				<th class='searchable sortable'><?php echo LANG['login_name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['full_name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['role']; ?></th>
				<th class='searchable sortable'><?php echo LANG['description']; ?></th>
				<th class=''><?php echo LANG['action']; ?></th>
			</tr>
		</thead>
		<?php
		$counter = 0;
		foreach($cl->getSystemUsers() as $u) {
			$counter ++;
			echo "<tr>";
			echo "<td><input type='checkbox' name='system_user_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblSystemUserData)'></td>";
			echo "<td>";
			if($u->ldap) echo "<img src='img/ldap-directory.dyn.svg' title='".LANG['ldap_account']."'>&nbsp;";
			if($u->locked) echo "<img src='img/lock.dyn.svg' title='".LANG['locked']."'>&nbsp;";
			echo  "<span id='spnSystemUserUsername".$u->id."'>".htmlspecialchars($u->username)."</span>";
			echo "</td>";
			echo "<td id='spnSystemUserFullname".$u->id."'>".htmlspecialchars($u->fullname)."</td>";
			echo "<td id='spnSystemUserRole".$u->id."' rawvalue='".$u->system_user_role_id."'>".htmlspecialchars($u->system_user_role_name)."</td>";
			echo "<td id='spnSystemUserDescription".$u->id."'>".htmlspecialchars($u->description)."</td>";
			echo "<td><button title='".LANG['edit']."' onclick='showDialogEditSystemUser(".$u->id.", spnSystemUserUsername".$u->id.".innerText, spnSystemUserFullname".$u->id.".innerText, spnSystemUserDescription".$u->id.".innerText, spnSystemUserRole".$u->id.".getAttribute(\"rawvalue\"), ".$u->ldap.")'><img src='img/edit.svg'></button></td>";
			echo "</tr>";
		}
		?>
		<tfoot>
			<tr>
				<td colspan='999'>
					<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
					<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
				</td>
			</tr>
		</tfoot>
		</table>

		<div class='controls'>
			<button onclick='showDialogCreateSystemUser()'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button>
			<span class='vl'></span>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='lockSelectedSystemUser("system_user_id[]")'><img src='img/lock.svg'>&nbsp;<?php echo LANG['lock']; ?></button>
			<button onclick='unlockSelectedSystemUser("system_user_id[]")'><img src='img/unlock.svg'>&nbsp;<?php echo LANG['unlock']; ?></button>
			<button onclick='confirmRemoveSelectedSystemUser("system_user_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
	</div>
</div>
<?php } ?>
