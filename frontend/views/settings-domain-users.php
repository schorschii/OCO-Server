<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

$tab = 'users';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('self_service_management'); ?></span></h1>
</div>

<div id='tabControlSettings' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='users' class='<?php if($tab=='users') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('domain_users_with_self_service_permissions'); ?></a>
		<a href='#' name='roles' class='<?php if($tab=='roles') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('roles'); ?></a>
	</div>
	<div class='tabcontents'>

		<div name='users' class='<?php if($tab=='users') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<div class='controls'>
						<span class='filler'></span>
						<button onclick='ldapSyncDomainUsers()' <?php if(empty(LDAP_SERVER)) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('ldap_sync'); ?></button>
					</div>
					<p><?php echo LANG('domain_user_self_service_management_hint'); ?>
					<table id='tblSelfServiceUserData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSelfServiceUserData, this.checked)'></th>
							<th class='searchable sortable'><?php echo LANG('id'); ?></th>
							<th class='searchable sortable'><?php echo LANG('login_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('display_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('role'); ?></th>
							<th class='searchable sortable'><?php echo LANG('last_login'); ?></th>
							<th class='searchable sortable'><?php echo LANG('created'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<?php
					$counter = 0;
					foreach($cl->getDomainUsers() as $u) {
						$counter ++;
						echo "<tr class='".($u->domain_user_role_id==null?'inactive':'')."'>";
						echo "<td><input type='checkbox' name='domain_user_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblSelfServiceUserData)'></td>";
						echo "<td>".htmlspecialchars($u->id)."</td>";
						echo "<td sort_key='".htmlspecialchars($u->username,ENT_QUOTES)."'>";
						if(!empty($u->ldap)) echo "<img src='img/ldap-directory.dyn.svg' title='".LANG('ldap_account')."'>&nbsp;";
						if(!empty($u->password)) echo "<img src='img/password.dyn.svg' title='".LANG('password_set')."'>&nbsp;";
						echo  "<span id='spnDomainUserUid".$u->id."' style='display:none'>".htmlspecialchars($u->uid)."</span>";
						echo  "<span id='spnDomainUserUsername".$u->id."'><a ".explorerLink('views/domain-users.php?id='.$u->id).">".htmlspecialchars($u->username)."</a></span>";
						echo "</td>";
						echo "<td id='spnDomainUserDisplayName".$u->id."'>".htmlspecialchars($u->display_name)."</td>";
						echo "<td id='spnDomainUserRole".$u->id."' rawvalue='".$u->domain_user_role_id."'>".htmlspecialchars($u->domain_user_role_name)."</td>";
						echo "<td>".htmlspecialchars($u->last_login)."</td>";
						echo "<td>".htmlspecialchars($u->created)."</td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditDomainUser(".$u->id.", spnDomainUserUid".$u->id.".innerText, spnDomainUserUsername".$u->id.".innerText, spnDomainUserDisplayName".$u->id.".innerText, spnDomainUserRole".$u->id.".getAttribute(\"rawvalue\"), ".$u->ldap.")'><img src='img/edit.dyn.svg'></button></td>";
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
										<button onclick='confirmRemoveSelectedDomainUser("domain_user_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
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
						<button onclick='showDialogEditDomainUserRole()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create_domain_user_role'); ?></button>
						<span class='filler'></span>
					</div>
					<table id='tblDomainUserRoleData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblDomainUserRoleData, this.checked)'></th>
							<th class='searchable sortable'><?php echo LANG('id'); ?></th>
							<th class='searchable sortable'><?php echo LANG('name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('permission_json'); ?></th>
							<th class='searchable sortable'><?php echo LANG('domain_users'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<?php
					$counter = 0;
					foreach($cl->getDomainUserRoles() as $r) {
						$counter ++;
						echo "<tr>";
						echo "<td><input type='checkbox' name='domain_user_role_id[]' value='".$r->id."' onchange='refreshCheckedCounter(tblSystemUserRoleData)'></td>";
						echo "<td>".htmlspecialchars($r->id)."</td>";
						echo "<td><span id='spnDomainUserRoleName".$r->id."'>".htmlspecialchars($r->name)."</span></td>";
						echo "<td class='subbuttons'>".htmlspecialchars(shorter($r->permissions, 100))." <button id='btnDomainUserRolePermissions".$r->id."' onclick='showDialog(\"".htmlspecialchars($r->name,ENT_QUOTES)."\",this.getAttribute(\"permissions\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' permissions='".htmlspecialchars(prettyJson($r->permissions),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
						echo "<td>".htmlspecialchars($r->domain_user_count)."</span></td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditDomainUserRole(".$r->id.", spnDomainUserRoleName".$r->id.".innerText, btnDomainUserRolePermissions".$r->id.".getAttribute(\"permissions\"))'><img src='img/edit.dyn.svg'></button></td>";
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
										<button onclick='confirmRemoveSelectedDomainUserRole("domain_user_role_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
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

