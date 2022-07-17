<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$tab = 'OwnSystemUserSettings';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG['settings']; ?></span></h1>
</div>

<div id='tabControlSettings' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='OwnSystemUserSettings' class='<?php if($tab=='OwnSystemUserSettings') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG['own_system_user_settings']; ?></a>
		<a href='#' name='SystemUserManagement' class='<?php if($tab=='SystemUserManagement') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG['system_user_management']; ?></a>
		<a href='#' name='ConfigurationOverview' class='<?php if($tab=='ConfigurationOverview') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG['configuration_overview']; ?></a>
	</div>
	<div class='tabcontents'>

		<div name='ConfigurationOverview' class='<?php if($tab=='ConfigurationOverview') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div>
					<p><?php echo LANG['change_settings_in_config_file']; ?></p>
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
							<th><?php echo LANG['assume_computer_offline_after']; ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(COMPUTER_OFFLINE_SECONDS)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['wol_shutdown_expiry_seconds']; ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(WOL_SHUTDOWN_EXPIRY_SECONDS)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['agent_update_interval']; ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(AGENT_UPDATE_INTERVAL)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['purge_succeeded_jobs_after']; ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_SUCCEEDED_JOBS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['purge_failed_jobs_after']; ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_FAILED_JOBS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['purge_logs_after']; ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_LOGS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['purge_domain_user_logons_after']; ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_DOMAIN_USER_LOGONS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['wol_satellites']; ?>:</th>
							<td><?php foreach(SATELLITE_WOL_SERVER as $s) echo htmlspecialchars($s['ADDRESS'].':'.$s['PORT']).'<br>'; ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div name='OwnSystemUserSettings' class='<?php if($tab=='OwnSystemUserSettings') echo 'active'; ?>'>
			<?php $ownSystemUser = $db->getSystemUser($_SESSION['oco_user_id']); ?>
			<div class='controls'>
				<button onclick='askNotificationPermission()'><img src='img/notification.dyn.svg'>&nbsp;<?php echo LANG['enable_notifications']; ?></button>
				<button onclick='showDialogEditOwnSystemUserPassword()' <?php if($currentSystemUser->ldap) echo 'disabled'; ?>><img src='img/password.dyn.svg'>&nbsp;<?php echo LANG['change_password']; ?></button>
			</div>
			<div class='details-abreast'>
				<div>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG['username']; ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->username); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['name']; ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->fullname); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['role']; ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->system_user_role_name); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['ldap_account']; ?>:</th>
							<td><?php if($ownSystemUser->ldap) echo LANG['yes']; else echo LANG['no']; ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['locked']; ?>:</th>
							<td><?php if($ownSystemUser->locked) echo LANG['yes']; else echo LANG['no']; ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['last_login']; ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->last_login); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG['created']; ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->created); ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div name='SystemUserManagement' class='<?php if($tab=='SystemUserManagement') echo 'active'; ?>'>
		<?php if($currentSystemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT, false)) { ?>
			<div class='controls'>
				<button onclick='showDialogCreateSystemUser()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG['add']; ?></button>
			</div>
			<div class='details-abreast'>
				<div>
					<table id='tblSystemUserData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemUserData, this.checked)'></th>
							<th class='searchable sortable'><?php echo LANG['login_name']; ?></th>
							<th class='searchable sortable'><?php echo LANG['full_name']; ?></th>
							<th class='searchable sortable'><?php echo LANG['role']; ?></th>
							<th class='searchable sortable'><?php echo LANG['description']; ?></th>
							<th class='searchable sortable'><?php echo LANG['last_login']; ?></th>
							<th class='searchable sortable'><?php echo LANG['created']; ?></th>
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
						echo "<td>".htmlspecialchars($u->last_login)."</td>";
						echo "<td>".htmlspecialchars($u->created)."</td>";
						echo "<td><button title='".LANG['edit']."' onclick='showDialogEditSystemUser(".$u->id.", spnSystemUserUsername".$u->id.".innerText, spnSystemUserFullname".$u->id.".innerText, spnSystemUserDescription".$u->id.".innerText, spnSystemUserRole".$u->id.".getAttribute(\"rawvalue\"), ".$u->ldap.")'><img src='img/edit.dyn.svg'></button></td>";
						echo "</tr>";
					}
					?>
					<tfoot>
						<tr>
							<td colspan='999'>
								<div class='spread'>
									<div>
										<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
										<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
									</div>
									<div>
										<button onclick='lockSelectedSystemUser("system_user_id[]")'><img src='img/lock.dyn.svg'>&nbsp;<?php echo LANG['lock']; ?></button>
										<button onclick='unlockSelectedSystemUser("system_user_id[]")'><img src='img/unlock.dyn.svg'>&nbsp;<?php echo LANG['unlock']; ?></button>
										<button onclick='confirmRemoveSelectedSystemUser("system_user_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
									</div>
								</div>
							</td>
						</tr>
					</tfoot>
					</table>
				</div>
			</div>
		<?php } ?>
		</div>

	</div>
</div>


