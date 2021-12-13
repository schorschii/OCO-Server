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
				<th><?php echo LANG['purge_domainuser_logons_after']; ?>:</th>
				<td><?php echo htmlspecialchars(PURGE_DOMAINUSER_LOGONS_AFTER); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['purge_logs_after']; ?>:</th>
				<td><?php echo htmlspecialchars(PURGE_LOGS_AFTER); ?></td>
			</tr>
		</table>
	</div>
	<div>
		<h2><?php echo LANG['user_settings']; ?></h2>
		<button onclick='askNotificationPermission()'><img src='img/notification.svg'>&nbsp;<?php echo LANG['enable_notifications']; ?></button>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['system_users']; ?></h2>
		<div class='controls'>
			<button onclick='showDialogAjax(L__CREATE_SYSTEM_USER, "views/dialog-system-user-add.php", DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO)'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button>
		</div>
		<table id='tblSystemuserData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemuserData, this.checked)'></th>
				<th class='searchable sortable'><?php echo LANG['login_name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['full_name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['description']; ?></th>
			</tr>
		</thead>
		<?php
		$counter = 0;
		foreach($db->getAllSystemuser() as $u) {
			$counter ++;
			echo "<tr>";
			echo "<td><input type='checkbox' name='systemuser_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblSystemuserData)'></td>";
			echo "<td>";
			if($u->ldap) echo "<img src='img/ldap-directory.dyn.svg' title='".LANG['ldap_account']."'>&nbsp;";
			if($u->locked) echo "<img src='img/lock.dyn.svg' title='".LANG['locked']."'>&nbsp;";
			echo  htmlspecialchars($u->username);
			echo "</td>";
			echo "<td>".htmlspecialchars($u->fullname)."</td>";
			echo "<td>".htmlspecialchars($u->description)."</td>";
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
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button id='btnChangePassword' onclick='showDialogAjax(L__NEW_PASSWORD, "views/dialog-system-user-change-password.php", DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO)'>
				<img src='img/edit.svg'>&nbsp;<?php echo LANG['new_password']; ?>
			</button>
			<button onclick='lockSelectedSystemuser("systemuser_id[]")'><img src='img/lock.svg'>&nbsp;<?php echo LANG['lock']; ?></button>
			<button onclick='unlockSelectedSystemuser("systemuser_id[]")'><img src='img/unlock.svg'>&nbsp;<?php echo LANG['unlock']; ?></button>
			<button onclick='confirmRemoveSelectedSystemuser("systemuser_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
	</div>
</div>
