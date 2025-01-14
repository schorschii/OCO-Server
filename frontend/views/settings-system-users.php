<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$tab = 'users';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$ldapActive = false;
$ldapServers = json_decode($db->settings->get('system-user-ldapsync')??'', true);
if(!empty($ldapServers) && is_array($ldapServers)) $ldapActive = true;
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('system_user_management'); ?></span></h1>
</div>

<div id='tabControlSettings' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='users' class='<?php if($tab=='users') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('system_users'); ?></a>
		<a href='#' name='roles' class='<?php if($tab=='roles') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('roles'); ?></a>
		<a href='#' name='log' class='<?php if($tab=='log') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('user_log'); ?></a>
	</div>
	<div class='tabcontents'>

		<div name='users' class='<?php if($tab=='users') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<div class='controls'>
						<button onclick='showDialogEditSystemUser()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create_system_user'); ?></button>
						<span class='filler'></span>
						<button onclick='showDialogEditLdapConfigSystemUsers()'><img src='img/ldap-directory.dyn.svg'>&nbsp;<?php echo LANG('ldap_config'); ?></button>
						<button onclick='ldapSyncSystemUsers()' <?php if(!$ldapActive) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('ldap_sync'); ?></button>
					</div>
					<table id='tblSystemUserData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' class='toggleAllChecked'></th>
							<th class='searchable sortable'><?php echo LANG('id'); ?></th>
							<th class='searchable sortable'><?php echo LANG('uid'); ?></th>
							<th class='searchable sortable'><?php echo LANG('login_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('display_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('role'); ?></th>
							<th class='searchable sortable'><?php echo LANG('last_login'); ?></th>
							<th class='searchable sortable'><?php echo LANG('created'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach($cl->getSystemUsers() as $u) {
						echo "<tr>";
						echo "<td><input type='checkbox' name='system_user_id[]' value='".$u->id."'></td>";
						echo "<td>".htmlspecialchars($u->id)."</td>";
						echo "<td>".htmlspecialchars($u->uid??'-')."</td>";
						echo "<td sort_key='".htmlspecialchars($u->username,ENT_QUOTES)."'>";
						if($u->ldap) echo "<img src='img/ldap-directory.dyn.svg' title='".LANG('ldap_account')."'>&nbsp;";
						if($u->locked) echo "<img src='img/lock.dyn.svg' title='".LANG('locked')."'>&nbsp;";
						echo htmlspecialchars($u->username);
						echo "</td>";
						echo "<td>".htmlspecialchars($u->display_name)."</td>";
						echo "<td>".htmlspecialchars($u->system_user_role_name)."</td>";
						echo "<td>".htmlspecialchars($u->last_login??'')."</td>";
						echo "<td>".htmlspecialchars($u->created)."</td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditSystemUser(".$u->id.")'><img src='img/edit.dyn.svg'></button></td>";
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
		</div>

		<div name='roles' class='<?php if($tab=='roles') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<div class='controls'>
						<button onclick='showDialogEditSystemUserRole()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create_system_user_role'); ?></button>
						<span class='filler'></span>
					</div>
					<table id='tblSystemUserRoleData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' class='toggleAllChecked'></th>
							<th class='searchable sortable'><?php echo LANG('id'); ?></th>
							<th class='searchable sortable'><?php echo LANG('name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('permission_json'); ?></th>
							<th class='searchable sortable'><?php echo LANG('system_users'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach($cl->getSystemUserRoles() as $r) {
						echo "<tr>";
						echo "<td><input type='checkbox' name='system_user_role_id[]' value='".$r->id."'></td>";
						echo "<td>".htmlspecialchars($r->id)."</td>";
						echo "<td>".htmlspecialchars($r->name)."</td>";
						echo "<td class='subbuttons'>".htmlspecialchars(shorter($r->permissions, 100))." <button id='btnSystemUserRolePermissions".$r->id."' onclick='showDialog(\"".htmlspecialchars($r->name,ENT_QUOTES)."\",this.getAttribute(\"permissions\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' permissions='".htmlspecialchars(prettyJson($r->permissions),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
						echo "<td>".htmlspecialchars($r->system_user_count)."</td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditSystemUserRole(".$r->id.")'><img src='img/edit.dyn.svg'></button></td>";
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
										<button onclick='confirmRemoveSelectedSystemUserRole("system_user_role_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
									</div>
								</div>
							</td>
						</tr>
					</tfoot>
					</table>
				</div>
			</div>
		</div>

		<div name='log' class='<?php if($tab=='log') echo 'active'; ?>'>
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
							foreach($db->selectAllLogEntryByObjectIdAndActions(null, 'oco.client', empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
								echo "<tr>";
								echo "<td>".htmlspecialchars($l->timestamp)."</td>";
								echo "<td>".htmlspecialchars($l->host)."</td>";
								echo "<td>".htmlspecialchars($l->user)."</td>";
								echo "<td>".htmlspecialchars($l->action)."</td>";
								echo "<td class='subbuttons'>".htmlspecialchars(shorter($l->data, 100))." <button onclick='showDialog(\"".htmlspecialchars($l->action,ENT_QUOTES)."\",this.getAttribute(\"data\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' data='".htmlspecialchars(prettyJson($l->data),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
								echo "</tr>";
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<div class='spread'>
										<div>
											<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
										</div>
										<div class='controls'>
											<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
											<?php if(empty($_GET['nolimit'])) { ?>
												<button onclick='rewriteUrlContentParameter({"nolimit":1}, true)'><img src='img/eye.dyn.svg'>&nbsp;<?php echo LANG('show_all'); ?></button>
											<?php } ?>
										</div>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>

	</div>
</div>

