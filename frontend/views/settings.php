<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

$tab = 'own-system-user-settings';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

$showSystemUserManagement = $currentSystemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT, false);
$showDeletedObjects = $currentSystemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_VIEW_DELETED_OBJECTS, false);
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('settings'); ?></span></h1>
</div>

<div id='tabControlSettings' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='own-system-user-settings' class='<?php if($tab=='own-system-user-settings') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('own_system_user_settings'); ?></a>
		<?php if($showSystemUserManagement) { ?>
			<a href='#' name='system-user-management' class='<?php if($tab=='system-user-management') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('system_user_management'); ?></a>
			<a href='#' name='user-log' class='<?php if($tab=='user-log') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('user_log'); ?></a>
		<?php } ?>
		<a href='#' name='configuration-overview' class='<?php if($tab=='configuration-overview') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('configuration_overview'); ?></a>
		<?php if($showDeletedObjects) { ?>
			<a href='#' name='deleted-objects-history' class='<?php if($tab=='deleted-objects-history') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('deleted_objects_history'); ?></a>
		<?php } ?>
	</div>
	<div class='tabcontents'>

		<div name='own-system-user-settings' class='<?php if($tab=='own-system-user-settings') echo 'active'; ?>'>
			<?php $ownSystemUser = $db->selectSystemUser($_SESSION['oco_user_id']); ?>
			<div class='details-abreast'>
				<div>
					<div class='controls'>
						<button onclick='askNotificationPermission()'><img src='img/notification.dyn.svg'>&nbsp;<?php echo LANG('enable_notifications'); ?></button>
						<button onclick='showDialogEditOwnSystemUserPassword()' <?php if($currentSystemUser->ldap) echo 'disabled'; ?>><img src='img/password.dyn.svg'>&nbsp;<?php echo LANG('change_password'); ?></button>
						<span class='filler'></span>
					</div>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('login_name'); ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->username); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('display_name'); ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->display_name); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('role'); ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->system_user_role_name); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('ldap_account'); ?>:</th>
							<td><?php if($ownSystemUser->ldap) echo LANG('yes'); else echo LANG('no'); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('locked'); ?>:</th>
							<td><?php if($ownSystemUser->locked) echo LANG('yes'); else echo LANG('no'); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('last_login'); ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->last_login); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('created'); ?>:</th>
							<td><?php echo htmlspecialchars($ownSystemUser->created); ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div name='system-user-management' class='<?php if($tab=='system-user-management') echo 'active'; ?>'>
		<?php if($showSystemUserManagement) { ?>
			<div class='details-abreast'>
				<div class='stickytable'>
					<div class='controls'>
						<button onclick='showDialogCreateSystemUser()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?></button>
						<button onclick='ldapSync()' <?php if(empty(LDAP_SERVER)) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('ldap_sync'); ?></button>
						<span class='filler'></span>
					</div>
					<table id='tblSystemUserData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemUserData, this.checked)'></th>
							<th class='searchable sortable'><?php echo LANG('login_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('display_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('role'); ?></th>
							<th class='searchable sortable'><?php echo LANG('description'); ?></th>
							<th class='searchable sortable'><?php echo LANG('last_login'); ?></th>
							<th class='searchable sortable'><?php echo LANG('created'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<?php
					$counter = 0;
					foreach($cl->getSystemUsers() as $u) {
						$counter ++;
						echo "<tr>";
						echo "<td><input type='checkbox' name='system_user_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblSystemUserData)'></td>";
						echo "<td>";
						if($u->ldap) echo "<img src='img/ldap-directory.dyn.svg' title='".LANG('ldap_account')."'>&nbsp;";
						if($u->locked) echo "<img src='img/lock.dyn.svg' title='".LANG('locked')."'>&nbsp;";
						echo  "<span id='spnSystemUserUid".$u->id."' style='display:none'>".htmlspecialchars($u->uid)."</span>";
						echo  "<span id='spnSystemUserUsername".$u->id."'>".htmlspecialchars($u->username)."</span>";
						echo "</td>";
						echo "<td id='spnSystemUserDisplayName".$u->id."'>".htmlspecialchars($u->display_name)."</td>";
						echo "<td id='spnSystemUserRole".$u->id."' rawvalue='".$u->system_user_role_id."'>".htmlspecialchars($u->system_user_role_name)."</td>";
						echo "<td id='spnSystemUserDescription".$u->id."'>".htmlspecialchars($u->description)."</td>";
						echo "<td>".htmlspecialchars($u->last_login)."</td>";
						echo "<td>".htmlspecialchars($u->created)."</td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditSystemUser(".$u->id.", spnSystemUserUid".$u->id.".innerText, spnSystemUserUsername".$u->id.".innerText, spnSystemUserDisplayName".$u->id.".innerText, spnSystemUserDescription".$u->id.".innerText, spnSystemUserRole".$u->id.".getAttribute(\"rawvalue\"), ".$u->ldap.")'><img src='img/edit.dyn.svg'></button></td>";
						echo "</tr>";
					}
					?>
					<tfoot>
						<tr>
							<td colspan='999'>
								<div class='spread'>
									<div>
										<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>,
										<span class='counter-checked'>0</span>&nbsp;<?php echo LANG('elements_checked'); ?>
									</div>
									<div class='controls'>
										<button onclick='lockSelectedSystemUser("system_user_id[]")'><img src='img/lock.dyn.svg'>&nbsp;<?php echo LANG('lock'); ?></button>
										<button onclick='unlockSelectedSystemUser("system_user_id[]")'><img src='img/unlock.dyn.svg'>&nbsp;<?php echo LANG('unlock'); ?></button>
										<button onclick='confirmRemoveSelectedSystemUser("system_user_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
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

		<div name='user-log' class='<?php if($tab=='user-log') echo 'active'; ?>'>
		<?php if($showSystemUserManagement) { ?>
			<div class='details-abreast'>
				<div class='stickytable'>
					<table id='tblUserLogData' class='list searchable sortable savesort margintop'>
						<thead>
							<tr>
								<th class='searchable sortable'><?php echo LANG('timestamp'); ?></th>
								<th class='searchable sortable'><?php echo LANG('ip_address'); ?></th>
								<th class='searchable sortable'><?php echo LANG('user'); ?></th>
								<th class='searchable sortable'><?php echo LANG('action'); ?></th>
								<th class='searchable sortable'><?php echo LANG('data'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$counter = 0;
							foreach($db->selectAllLogEntryByObjectIdAndActions(null, 'oco.client', empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
								$counter ++;
								echo "<tr>";
								echo "<td>".htmlspecialchars($l->timestamp)."</td>";
								echo "<td>".htmlspecialchars($l->host)."</td>";
								echo "<td>".htmlspecialchars($l->user)."</td>";
								echo "<td>".htmlspecialchars($l->action)."</td>";
								echo "<td class='subbuttons'>".htmlspecialchars(shorter($l->data, 100))." <button onclick='event.preventDefault();showDialog(\"".htmlspecialchars($l->action,ENT_QUOTES)."\",this.getAttribute(\"data\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' data='".htmlspecialchars(prettyJson($l->data),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
								echo "</tr>";
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<div class='spread'>
										<div>
											<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>
										</div>
										<div class='controls'>
											<button onclick='event.preventDefault();downloadTableCsv("tblSoftwareInventoryData")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
											<?php if(empty($_GET['nolimit'])) { ?>
												<button onclick='rewriteUrlContentParameter(currentExplorerContentUrl, {"nolimit":1});refreshContent()'><img src='img/eye.dyn.svg'>&nbsp;<?php echo LANG('show_all'); ?></button>
											<?php } ?>
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

		<div name='configuration-overview' class='<?php if($tab=='configuration-overview') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div>
					<div class='alert info margintop'><?php echo LANG('change_settings_in_config_file'); ?></div>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('client_api_enabled'); ?>:</th>
							<td><?php if(CLIENT_API_ENABLED) echo LANG('yes'); else echo LANG('no'); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('agent_registration_enabled'); ?>:</th>
							<td><?php if(AGENT_SELF_REGISTRATION_ENABLED) echo LANG('yes'); else echo LANG('no'); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('assume_computer_offline_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(COMPUTER_OFFLINE_SECONDS)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('wol_shutdown_expiry_seconds'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(WOL_SHUTDOWN_EXPIRY_SECONDS)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('agent_update_interval'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(AGENT_UPDATE_INTERVAL)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_succeeded_jobs_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_SUCCEEDED_JOBS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_failed_jobs_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_FAILED_JOBS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_logs_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_LOGS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_domain_user_logons_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_DOMAIN_USER_LOGONS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('wol_satellites'); ?>:</th>
							<td><?php foreach(SATELLITE_WOL_SERVER as $s) echo htmlspecialchars($s['ADDRESS'].':'.$s['PORT']).'<br>'; ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('extensions'); ?>:</th>
							<td><?php foreach($ext->getLoadedExtensions() as $e) echo htmlspecialchars($e['name'].' ('.$e['id'].', v'.$e['version'].', '.$e['author'].')').'<br>'; ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div name='deleted-objects-history' class='<?php if($tab=='deleted-objects-history') echo 'active'; ?>'>
		<?php if($showDeletedObjects) { ?>
			<div class='details-abreast'>
				<div class='stickytable'>
					<table id='tblUserLogData' class='list searchable sortable savesort margintop'>
						<thead>
							<tr>
								<th class='searchable sortable'><?php echo LANG('timestamp'); ?></th>
								<th class='searchable sortable'><?php echo LANG('ip_address'); ?></th>
								<th class='searchable sortable'><?php echo LANG('user'); ?></th>
								<th class='searchable sortable'><?php echo LANG('action'); ?></th>
								<th class='searchable sortable'><?php echo LANG('object_id'); ?></th>
								<th class='searchable sortable'><?php echo LANG('data'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$counter = 0;
							foreach($db->selectAllLogEntryByObjectIdAndActions(false, ['oco.computer.delete', 'oco.package.delete', 'oco.package_family.delete', 'oco.job_container.delete', 'oco.deployment_rule.delete', 'oco.domain_user.delete', 'oco.report.delete'], empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
								$counter ++;
								echo "<tr>";
								echo "<td>".htmlspecialchars($l->timestamp)."</td>";
								echo "<td>".htmlspecialchars($l->host)."</td>";
								echo "<td>".htmlspecialchars($l->user)."</td>";
								echo "<td>".htmlspecialchars($l->action)."</td>";
								echo "<td>".htmlspecialchars($l->object_id)."</td>";
								echo "<td class='subbuttons'>".htmlspecialchars(shorter($l->data, 100))." <button onclick='event.preventDefault();showDialog(\"".htmlspecialchars($l->action,ENT_QUOTES)."\",this.getAttribute(\"data\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' data='".htmlspecialchars(prettyJson($l->data),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
								echo "</tr>";
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<div class='spread'>
										<div>
											<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>
										</div>
										<div class='controls'>
											<button onclick='event.preventDefault();downloadTableCsv("tblSoftwareInventoryData")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
											<?php if(empty($_GET['nolimit'])) { ?>
												<button onclick='rewriteUrlContentParameter(currentExplorerContentUrl, {"nolimit":1});refreshContent()'><img src='img/eye.dyn.svg'>&nbsp;<?php echo LANG('show_all'); ?></button>
											<?php } ?>
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
