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
			<a href='#' name='system-user-role-management' class='<?php if($tab=='system-user-role-management') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('system_user_role_management'); ?></a>
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
						<button onclick='showDialogEditSystemUser()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create_system_user'); ?></button>
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

		<div name='system-user-role-management' class='<?php if($tab=='system-user-role-management') echo 'active'; ?>'>
		<?php if($showSystemUserManagement) { ?>
			<div class='details-abreast'>
				<div class='stickytable'>
					<div class='controls'>
						<button onclick='showDialogEditSystemUserRole()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create_system_user_role'); ?></button>
						<span class='filler'></span>
					</div>
					<table id='tblSystemUserRoleData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemUserRoleData, this.checked)'></th>
							<th class='searchable sortable'><?php echo LANG('id'); ?></th>
							<th class='searchable sortable'><?php echo LANG('name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('permission_json'); ?></th>
							<th class='searchable sortable'><?php echo LANG('system_users'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<?php
					$counter = 0;
					foreach($cl->getSystemUserRoles() as $r) {
						$counter ++;
						echo "<tr>";
						echo "<td><input type='checkbox' name='system_user_role_id[]' value='".$r->id."' onchange='refreshCheckedCounter(tblSystemUserRoleData)'></td>";
						echo "<td>".htmlspecialchars($r->id)."</td>";
						echo "<td><span id='spnSystemUserRoleName".$r->id."'>".htmlspecialchars($r->name)."</span></td>";
						echo "<td class='subbuttons'>".htmlspecialchars(shorter($r->permissions, 100))." <button id='btnSystemUserRolePermissions".$r->id."' onclick='event.preventDefault();showDialog(\"".htmlspecialchars($r->name,ENT_QUOTES)."\",this.getAttribute(\"permissions\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' permissions='".htmlspecialchars(prettyJson($r->permissions),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
						echo "<td>".htmlspecialchars($r->system_user_count)."</span></td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditSystemUserRole(".$r->id.", spnSystemUserRoleName".$r->id.".innerText, btnSystemUserRolePermissions".$r->id.".getAttribute(\"permissions\"))'><img src='img/edit.dyn.svg'></button></td>";
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
										<button onclick='confirmRemoveSelectedSystemUserRole("system_user_role_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
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
					<h2><?php echo LANG('oco_configuration'); ?></h2>
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
					</table>
					<p><?php echo LANG('change_settings_in_config_file'); ?></p>
				</div>
				<div>
					<h2><?php echo LANG('server_environment'); ?></h2>
					<table class='list'>
						<tr>
							<th><?php echo LANG('webserver_version'); ?>:</th>
							<td><?php echo htmlspecialchars(apache_get_version()); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('php_version'); ?>:</th>
							<td><?php echo htmlspecialchars(phpversion()); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('post_max_size'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('post_max_size')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('upload_max_filesize'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('upload_max_filesize')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('max_input_time'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('max_input_time')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('max_execution_time'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('max_execution_time')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('memory_limit'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('memory_limit')); ?></td>
						</tr>
					</table>
				</div>
			</div>
			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('wol_satellites'); ?></h2>
					<?php if(count(SATELLITE_WOL_SERVER) == 0) { ?>
						<div class='alert info'>Keine WOL-Satelliten-Server definiert</div>
					<?php } else { ?>
					<table class='list'>
						<tr>
							<th><?php echo LANG('address'); ?></th>
							<th><?php echo LANG('port'); ?></th>
						</tr>
						<?php foreach(SATELLITE_WOL_SERVER as $s) { ?>
						<tr>
							<td><?php echo htmlspecialchars($s['ADDRESS']); ?></td>
							<td><?php echo htmlspecialchars($s['PORT']); ?></td>
						</tr>
						<?php } ?>
					</table>
					<?php } ?>
				</div>
				<div>
				<h2><?php echo LANG('extensions'); ?></h2>
					<?php if(count($ext->getLoadedExtensions()) == 0) { ?>
						<div class='alert info'>Keine Erweiterungen geladen</div>
					<?php } else { ?>
					<table class='list'>
						<tr>
							<th><?php echo LANG('id'); ?></th>
							<th><?php echo LANG('name'); ?></th>
							<th><?php echo LANG('version'); ?></th>
							<th><?php echo LANG('author'); ?></th>
						</tr>
						<?php foreach($ext->getLoadedExtensions() as $e) { ?>
						<tr>
							<td><?php echo htmlspecialchars($e['id']); ?></td>
							<td><?php echo htmlspecialchars($e['name']); ?></td>
							<td><?php echo htmlspecialchars($e['version']); ?></td>
							<td><?php echo htmlspecialchars($e['author']); ?></td>
						</tr>
						<?php } ?>
					</table>
					<?php } ?>
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
